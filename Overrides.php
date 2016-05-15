<?php
/**
 * @file
 * These are here to map the overridable classes for your IDE.
 */

namespace Lightning\View;

class API extends \Overridable\Lightning\View\API {}
class Page extends \Overridable\Lightning\View\Page {}

namespace Lightning\Model;

class Blog extends \Overridable\Lightning\Model\Blog {}
class BlogPost extends \Overridable\Lightning\Model\BlogPost {}
class Calendar extends \Overridable\Lightning\Model\Calendar {}
class CMS extends \Overridable\Lightning\Model\CMS {}
class Message extends \Overridable\Lightning\Model\Message {}
class Page extends \Overridable\Lightning\Model\Page {}
class Permissions extends \Overridable\Lightning\Model\Permissions {}
class SocialAuth extends \Overridable\Lightning\Model\SocialAuth {}
class Token extends \Overridable\Lightning\Model\Token {}
class User extends \Overridable\Lightning\Model\User {}

namespace Lightning\Tools;

class ClientUser extends \Overridable\Lightning\Tools\ClientUser {}
class Request extends \Overridable\Lightning\Tools\Request {}
class Session extends \Overridable\Lightning\Tools\Session {}

namespace Lightning\Tools\Security;

class Encryption extends \Overridable\Lightning\Tools\Security\Encryption {}
class Random extends \Overridable\Lightning\Tools\Security\Random {}

namespace Lightning\Tools\SocialDrivers;

abstract class SocialMediaApi extends \Overridable\Lightning\Tools\SocialDrivers\SocialMediaApi {}
