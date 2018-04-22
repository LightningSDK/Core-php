<?php
/**
 * @file
 * These are here to map the overridable classes for your IDE.
 */

namespace Lightning\View;

class API extends \Lightning\View\APIOverridable {}
class Page extends \Lightning\View\PageOverridable {}

namespace Lightning\Model;

class Blog extends \Lightning\Model\BlogOverridable {}
class BlogPost extends \Lightning\Model\BlogPostOverridable {}
class Calendar extends \Lightning\Model\CalendarOverridable {}
class CMS extends \Lightning\Model\CMSOverridable {}
class Message extends \Lightning\Model\MessageOverridable {}
class Page extends \Lightning\Model\PageOverridable {}
class Permissions extends \Lightning\Model\PermissionsOverridable {}
class SocialAuth extends \Lightning\Model\SocialAuthOverridable {}
class Token extends \Lightning\Model\TokenOverridable {}
class Tracker extends \Lightning\Model\TrackerOverridable {}
class User extends \Lightning\Model\UserOverridable {}

namespace Lightning\Model\Mailing;
class Lists extends \Lightning\Model\Mailing\ListsOverridable {}

namespace Lightning\Tools;

class ClientUser extends \Lightning\Tools\ClientUserOverridable {}
class Logger extends \Lightning\Tools\LoggerOverridable {}
class Request extends \Lightning\Tools\RequestOverridable {}

namespace Lightning\Tools\Session;
class DBSession extends \Lightning\Tools\Session\DBSessionOverridable {}
class BrowserSession extends \Lightning\Tools\Session\BrowserSessionOverridable {}

namespace Lightning\Tools\Security;

class Encryption extends \Lightning\Tools\Security\EncryptionOverridable {}
class Random extends \Lightning\Tools\Security\RandomOverridable {}

namespace Lightning\Tools\SocialDrivers;

abstract class SocialMediaApi extends \Lightning\Tools\SocialDrivers\SocialMediaApiOverridable {}
