<?php
/**
 * @file
 * These are here to map the overridable classes for your IDE.
 */

namespace Lightning\View;

class Page extends \Overridable\Lightning\View\Page {}
class API extends \Overridable\Lightning\View\API {}

namespace Lightning\Model;

class Blog extends \Overridable\Lightning\Model\Blog {}
class Token extends \Overridable\Lightning\Model\Token {}
class User extends \Overridable\Lightning\Model\User {}
class Permissions extends \Overridable\Lightning\Model\Permissions {}

namespace Lightning\Tools;

class Session extends \Overridable\Lightning\Tools\Session {}
class Request extends \Overridable\Lightning\Tools\Request {}
class ClientUser extends \Overridable\Lightning\Tools\ClientUser {}

namespace Lightning\Tools\Security;

class Encryption extends \Overridable\Lightning\Tools\Security\Encryption {}
class Random extends \Overridable\Lightning\Tools\Security\Random {}
