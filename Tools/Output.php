<?
/**
 * @file
 * A class for managing output.
 */

namespace Lightning\Tools;

/**
 * Class Output
 *
 * @package Lightning\Tools
 */
class Output {
    /**
     * The default output for access denied errors.
     */
    const ACCESS_DENIED = 1;

    /**
     * The default output for successful executions.
     */
    const SUCCESS = 2;

    /**
     * Output data as json and end the request.
     *
     * @param array|integer $data
     *   The data to output as JSON.
     */
    public static function json($data) {
        // Predefined outputs.
        if ($data == self::ACCESS_DENIED) {
            $data = array('status' => 'access_denied');
        }
        elseif ($data == self::SUCCESS) {
            $data = array('status' => 'success');
        }

        // Add errors and messages.
        $data['errors'] = Messenger::getErrors();
        $data['messages'] = Messenger::getErrors();

        // Output the data.
        header('Content-type: application/json');
        echo json_encode($data);

        // Terminate the script.
        exit;
    }

    public static function XMLSegment($items, $type = null) {
        $output = '';
        foreach ($items as $key => $item) {
            if (is_numeric($key) && $type) {
                $key = $type;
            }
            if (is_array($item)) {
                $output .= "<$key>" . self::XMLSegment($item) . "</$key>";
            } else {
                $output .= "<$key>" . Scrub::toHTML($item) . "</$key>";
            }
        }
        return $output;
    }

    /**
     * Load and render the access denied page.
     */
    public static function accessDenied() {
        exit;
    }
}