<?php

namespace lightningsdk\core\Tools;

class AuditLog {
    protected static $config;

    public static function init() {
        self::$config = COnfiguration::get('audit_log') ?: [
            'type' => 'file',
            'path' => '../audit.log',
        ];
    }

    /**
     * Create an audit log entry.
     *
     * @param string $type
     *   The log type. This must be an alphanumeric string with no spaces. It should be unique to the condition it is being logged.
     * @param array|string $data
     *   Any additional data to log.
     */
    public static function log($type, $data) {
        $user = ClientUser::getInstance();
        $log_data = [
            'user_id' => $user->id,
            'admin_user' => $user->impersonatingParentUser() ?: 0,
            'type' => $type,
            'data' => $data,
        ];
        self::init();
        if (self::$config['type'] == 'file') {
            $string = implode(' ', [$log_data['type'], Request::getIP(), $log_data['user_id'], $log_data['admin_user'], json_encode($log_data['data'])]);
            Logger::message($string, self::$config['path']);
        } else {
            $log_data['ip'] = Request::getIP();
            $log_data['time'] = time();
            switch (self::$config['type']) {
                case 'sql':
                    $log_data['data'] = json_encode($log_data['data']);
                    Database::getInstance()->insert('audit_log', $log_data);
                    break;
                case 'mongo':
                    // If a collection name is set, this all goes into a single collection.
                    // If no collection name, the log type will be the name of the collection.
                    $collection_name = !empty(self::$config['collection']) ? self::$config['collection'] : $log_data['type'];
                    $collection = Mongo::getInstance()->selectCollection(self::$config['db'], $collection_name);
                    $collection->insert($log_data);
                    break;
            }
        }
    }
}
