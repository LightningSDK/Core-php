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
class User extends \Lightning\Model\UserOverridable {}

namespace Lightning\Tools;

class ClientUser extends \Lightning\Tools\ClientUserOverridable {}
class Request extends \Lightning\Tools\RequestOverridable {}
class Session extends \Lightning\Tools\SessionOverridable {}

namespace Lightning\Tools\Security;

class Encryption extends \Lightning\Tools\Security\EncryptionOverridable {}
class Random extends \Lightning\Tools\Security\RandomOverridable {}

namespace Lightning\Tools\SocialDrivers;

abstract class SocialMediaApi extends \Lightning\Tools\SocialDrivers\SocialMediaApiOverridable {}
