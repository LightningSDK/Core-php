<?php
/**
 * @file
 * These are here to map the overridable classes for your IDE.
 */

namespace lightningsdk\core\View;

class API extends \lightningsdk\core\View\APIOverridable {}
class Page extends \lightningsdk\core\View\PageOverridable {}

namespace lightningsdk\core\Model;

class Calendar extends \lightningsdk\core\Model\CalendarOverridable {}
class CMS extends \lightningsdk\core\Model\CMSOverridable {}
class Message extends \lightningsdk\core\Model\MessageOverridable {}
class Page extends \lightningsdk\core\Model\PageOverridable {}
class Permissions extends \lightningsdk\core\Model\PermissionsOverridable {}
class SocialAuth extends \lightningsdk\core\Model\SocialAuthOverridable {}
class Token extends \lightningsdk\core\Model\TokenOverridable {}
class Tracker extends \lightningsdk\core\Model\TrackerOverridable {}
class User extends \lightningsdk\core\Model\UserOverridable {}

namespace lightningsdk\core\Model\Mailing;
class Lists extends \lightningsdk\core\Model\Mailing\ListsOverridable {}

namespace lightningsdk\core\Tools;

class ClientUser extends \lightningsdk\core\Tools\ClientUserOverridable {}
class Logger extends \lightningsdk\core\Tools\LoggerOverridable {}
class Request extends \lightningsdk\core\Tools\RequestOverridable {}

namespace lightningsdk\core\Tools\Session;
class DBSession extends \lightningsdk\core\Tools\Session\DBSessionOverridable {}
class BrowserSession extends \lightningsdk\core\Tools\Session\BrowserSessionOverridable {}

namespace lightningsdk\core\Tools\Security;

class Encryption extends \lightningsdk\core\Tools\Security\EncryptionOverridable {}
class Random extends \lightningsdk\core\Tools\Security\RandomOverridable {}

namespace lightningsdk\core\Tools\SocialDrivers;

abstract class SocialMediaApi extends \lightningsdk\core\Tools\SocialDrivers\SocialMediaApiOverridable {}
