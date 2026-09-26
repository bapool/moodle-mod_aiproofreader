<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Sends AI Proofreader prompts to the AI, with optional image input.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader;

/**
 * Wraps the two ways this plugin can reach an AI model.
 *
 * Moodle 4.5's AI subsystem (core_ai generate_text) only accepts a single
 * text prompt, so it cannot carry images. When the site has turned on
 * "Send images to the AI" and the activity's instructions or Additional
 * files contain images, the prompt and images are sent together straight
 * to an OpenAI-compatible chat completions endpoint (for example vLLM
 * serving a vision-language model). In every other case - and as a
 * fallback if that direct call fails - the normal core_ai text-only path
 * is used, exactly as before.
 */
class ai_client {
    /** @var string[] Image mimetypes that are sent to a vision model. */
    public const IMAGE_MIMETYPES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    /** @var int Longest image edge, in pixels, after resizing. */
    public const MAX_IMAGE_EDGE = 1568;

    /**
     * Sends a prompt to the AI and returns the result.
     *
     * @param \stdClass $aiproofreader The activity instance record
     * @param \context_module $context
     * @param int $userid The student the request is made on behalf of
     * @param string $prompt
     * @return array With keys success (bool), content (string), modellabel (string), error (string)
     */
    public static function generate(\stdClass $aiproofreader, \context_module $context, int $userid, string $prompt): array {
        if (self::vision_enabled()) {
            $images = self::get_instruction_images($aiproofreader, $context);
            if (!empty($images)) {
                $result = self::generate_with_images($prompt, $images);
                if ($result['success']) {
                    return $result;
                }
                // Never leave a student without feedback just because the
                // image request failed - retry text-only through core_ai.
                debugging('mod_aiproofreader: image AI request failed, falling back to text only: '
                    . $result['error'], DEBUG_DEVELOPER);
            }
        }

        return self::generate_with_core_ai($context, $userid, $prompt);
    }

    /**
     * Whether the site has image input turned on and configured.
     *
     * @return bool
     */
    public static function vision_enabled(): bool {
        return !empty(get_config('aiproofreader', 'visionenabled'))
            && trim((string) get_config('aiproofreader', 'visionendpoint')) !== ''
            && trim((string) get_config('aiproofreader', 'visionmodel')) !== '';
    }

    /**
     * The text-only path through Moodle's AI subsystem (unchanged behaviour).
     *
     * @param \context_module $context
     * @param int $userid
     * @param string $prompt
     * @return array
     */
    protected static function generate_with_core_ai(\context_module $context, int $userid, string $prompt): array {
        $result = ['success' => false, 'content' => '', 'modellabel' => '', 'error' => ''];

        try {
            $action = new \core_ai\aiactions\generate_text(
                contextid: $context->id,
                userid: $userid,
                prompttext: $prompt
            );
            $manager = \core\di::get(\core_ai\manager::class);
            $response = $manager->process_action($action);

            if ($response->get_success()) {
                $result['success'] = true;
                $result['content'] = $response->get_response_data()['generatedcontent'] ?? '';
                $result['modellabel'] = aiproofreader_build_ai_model_label($response);
            } else {
                $result['error'] = $response->get_errormessage();
                if (empty($result['error'])) {
                    $result['error'] = get_string('aiunknownerror', 'aiproofreader');
                }
            }
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Sends the prompt plus images to the configured OpenAI-compatible
     * chat completions endpoint.
     *
     * @param string $prompt
     * @param string[] $images Data URIs (data:image/...;base64,...)
     * @return array
     */
    protected static function generate_with_images(string $prompt, array $images): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $result = ['success' => false, 'content' => '', 'modellabel' => '', 'error' => ''];

        $endpoint = trim((string) get_config('aiproofreader', 'visionendpoint'));
        $model = trim((string) get_config('aiproofreader', 'visionmodel'));
        $apikey = trim((string) get_config('aiproofreader', 'visionapikey'));
        $timeout = (int) get_config('aiproofreader', 'visiontimeout');
        if ($timeout <= 0) {
            $timeout = 180;
        }

        $prompt .= "\n\n" . get_string('aiprompt_imagesnote', 'aiproofreader', count($images));

        $content = [['type' => 'text', 'text' => $prompt]];
        foreach ($images as $datauri) {
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $datauri]];
        }

        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $content],
            ],
        ];

        $headers = ['Content-Type: application/json'];
        if ($apikey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apikey;
        }

        try {
            $curl = new \curl();
            $curl->setHeader($headers);
            $curl->setopt([
                'CURLOPT_TIMEOUT' => $timeout,
                'CURLOPT_CONNECTTIMEOUT' => 15,
            ]);
            $raw = $curl->post($endpoint, json_encode($body));
            $info = $curl->get_info();
            $httpcode = (int) ($info['http_code'] ?? 0);
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
            return $result;
        }

        if ($curl->get_errno()) {
            $result['error'] = $curl->error;
            return $result;
        }

        $decoded = json_decode((string) $raw, true);

        if ($httpcode !== 200 || !is_array($decoded)) {
            $message = is_array($decoded) ? ($decoded['error']['message'] ?? $decoded['message'] ?? '') : '';
            $result['error'] = 'HTTP ' . $httpcode . ($message !== '' ? ': ' . $message : '');
            return $result;
        }

        $text = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            $result['error'] = get_string('aiunknownerror', 'aiproofreader');
            return $result;
        }

        // Some models include their reasoning in <think> tags - it is never
        // meant for the student or teacher.
        $text = trim(preg_replace('#<think>.*?</think>#is', '', $text));

        $result['success'] = true;
        $result['content'] = $text;
        $result['modellabel'] = aiproofreader_format_ai_model_label($decoded['model'] ?? $model);

        return $result;
    }

    /**
     * Collects the images from the activity's instructions and Additional
     * files, resized and encoded as data URIs, up to the site's limit.
     *
     * Only images still referenced in the instructions text are included,
     * so an image the teacher later deleted from the description (the
     * file can linger in the file area) is not sent.
     *
     * @param \stdClass $aiproofreader
     * @param \context_module $context
     * @return string[]
     */
    public static function get_instruction_images(\stdClass $aiproofreader, \context_module $context): array {
        $max = (int) get_config('aiproofreader', 'visionmaximages');
        if ($max <= 0) {
            $max = 4;
        }

        $fs = get_file_storage();
        $candidates = [];

        $intro = (string) ($aiproofreader->intro ?? '');
        foreach ($fs->get_area_files($context->id, 'mod_aiproofreader', 'intro', 0, 'filename', false) as $file) {
            $name = $file->get_filename();
            $referenced = strpos($intro, '@@PLUGINFILE@@/' . rawurlencode($name)) !== false
                || strpos($intro, '@@PLUGINFILE@@/' . $name) !== false;
            if ($referenced) {
                $candidates[] = $file;
            }
        }

        foreach ($fs->get_area_files($context->id, 'mod_aiproofreader', 'additionalfiles', 0, 'filename', false) as $file) {
            $candidates[] = $file;
        }

        $images = [];
        foreach ($candidates as $file) {
            if (count($images) >= $max) {
                break;
            }
            if (!in_array($file->get_mimetype(), self::IMAGE_MIMETYPES)) {
                continue;
            }
            $datauri = self::image_to_data_uri($file);
            if ($datauri !== null) {
                $images[] = $datauri;
            }
        }

        return $images;
    }

    /**
     * Resizes (if needed) and base64-encodes one stored image.
     *
     * @param \stored_file $file
     * @return string|null Data URI, or null if the image could not be read
     */
    protected static function image_to_data_uri(\stored_file $file): ?string {
        $content = $file->get_content();
        if ($content === '' || $content === false) {
            return null;
        }

        $mimetype = $file->get_mimetype();

        if (function_exists('imagecreatefromstring')) {
            $size = @getimagesizefromstring($content);
            $image = @imagecreatefromstring($content);
            if ($size && $image) {
                [$width, $height] = $size;
                $scale = min(1, self::MAX_IMAGE_EDGE / max($width, $height));
                $newwidth = max(1, (int) round($width * $scale));
                $newheight = max(1, (int) round($height * $scale));

                // Flatten onto white (drops transparency, which vision models
                // don't need) and re-encode as JPEG to keep requests small.
                $canvas = imagecreatetruecolor($newwidth, $newheight);
                imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
                imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

                ob_start();
                imagejpeg($canvas, null, 85);
                $content = ob_get_clean();
                $mimetype = 'image/jpeg';

                imagedestroy($canvas);
                imagedestroy($image);
            }
        }

        return 'data:' . $mimetype . ';base64,' . base64_encode($content);
    }
}
