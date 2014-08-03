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
     * Output data as json and end the request.
     *
     * @param array $data
     *   The data to output as JSON.
     */
    public function json($data) {
        header('Content-type: application/json');
        echo json_encode($data);
        exit;
    }
}