UPDATE `engine4_core_modules` SET `version` = '4.11' WHERE `name` = 'ynmobile';

DROP TABLE IF EXISTS `engine4_ynmobile_menuitems`;
CREATE TABLE IF NOT EXISTS `engine4_ynmobile_menuitems` (
  `menuitem_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `module` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT 'Core',
  `module_alt` varchar(32) NOT NULL,
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `order` smallint(6) NOT NULL DEFAULT '999',
  `label` varchar(50) NOT NULL,
  `layout` varchar(50) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `uri` varchar(50) NOT NULL,
  `menu` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`menuitem_id`),
  UNIQUE KEY `name` (`name`),
  KEY `LOOKUP` (`name`,`order`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=53 ;

--
-- Dumping data for table `engine4_ynmobile_menuitems`
--

INSERT INTO `engine4_ynmobile_menuitems` (`menuitem_id`, `name`, `module`, `module_alt`, `enabled`, `order`, `label`, `layout`, `icon`, `uri`, `menu`) VALUES
(29, 'activity', 'core', '', 1, 1, 'News Feed', 'activity', 'ion-card', '/app/newsfeed', 1),
(30, 'event', 'event', 'ynevent', 1, 999, 'Events', 'event', 'ion-ios-calendar-outline', '/app/events', 1),
(32, 'friends', 'core', '', 1, 3, 'Friends', 'friend', 'ion-ios-people-outline', '/app/friends', 1),
(33, 'photo', 'album', 'advalbum', 1, 999, 'Photos', 'photo', 'ion-ios-photos-outline', '/app/photos', 1),
(35, 'music', 'music', 'mp3music', 1, 4, 'Music', 'music', 'ion-ios-musical-notes', '/app/music_playlists', 1),
(36, 'video', 'video', 'ynvideo', 1, 999, 'Videos', 'video', 'ion-ios-film-outline', '/app/videos', 1),
(38, 'message', 'core', '', 1, 9, 'Messages', 'message', 'ion-ios-email-outline', '/app/messages', 1),
(41, 'forum', 'forum', 'ynforum', 1, 999, 'Forums', 'forum', 'ion-ios-bookmarks-outline', '/app/forums', 1),
(42, 'poll', 'poll', '', 1, 10, 'Polls', 'poll', 'ion-ios-analytics-outline', '/app/polls', 1),
(43, 'group', 'group', 'advgroup', 1, 999, 'Groups', 'group', 'ion-ios-circle-outline', '/app/groups', 1),
(44, 'blog', 'blog', 'ynblog', 1, 11, 'Blogs', 'blog', 'ion-ios-paper-outline', '/app/blogs', 1),
(45, 'classified', 'classified', '', 1, 12, 'Classifieds', 'classified', 'ion-ios-briefcase-outline', '/app/classifieds', 1),
(46, 'subscription', 'core', '', 1, 13, 'Memberships', 'subscription', 'ion-ribbon-b', '/app/subscriptions', 1),
(47, 'search', 'core', '', 1, 2, 'Search', 'search', 'ion-ios-search', '/app/search', 1),
(48, 'ynjobposting', 'ynjobposting', '', 1, 7, 'Job Posting', 'ynjobposting', 'ion-ios-glasses-outline', '/app/ynjobposting_jobs', 1),
(49, 'ynlisting', 'ynjobposting', '', 1, 6, 'Listings', 'ynlisting', 'ion-ios-pricetags-outline', '/app/listings', 1),
(50, 'ultimatenews', 'ultimatenews', '', 1, 5, 'Ultimate News', 'ultimatenews', 'ion-social-rss-outline', '/app/ultimatenews', 1),
(51, 'setting', 'core', '', 1, 14, 'Settings', 'setting', 'ion-ios-gear-outline', '/app/settings', 1),
(52, 'member', 'core', '', 1, 8, 'Members', 'member', 'ion-ios-personadd-outline', '/app/members', 1);


UPDATE `engine4_core_menuitems` SET  `enabled` =  '1' WHERE  `engine4_core_menuitems`.`name` ='ynmobile_admin_main_menus';

CREATE TABLE IF NOT EXISTS `engine4_ynmobile_themes` (
  `theme_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL DEFAULT '',
  `is_publish` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_test` tinyint(1) unsigned NOT NULL,
  `build_number` int(11) NOT NULL DEFAULT '0',
  `variables_text` text NOT NULL,
  `creation_date` datetime NOT NULL,
  `modified_date` datetime NOT NULL,
  PRIMARY KEY (`theme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


UPDATE `engine4_core_modules` SET `version` = '4.11' WHERE `name` = 'ynmobile';

INSERT IGNORE INTO `engine4_core_settings` (`name`,`value`) VALUES ('ynmobile.theme.number','1434004901');

INSERT IGNORE INTO `engine4_ynmobile_themes` (`theme_id`, `name`, `is_publish`, `is_test`, `build_number`, `variables_text`, `creation_date`, `modified_date`) VALUES
(7, 'Default', 1, 0, 1434004901, '{"positive":"#01a0db"}', '2015-06-11 06:41:46', '2015-06-11 06:41:46');

INSERT IGNORE INTO `engine4_core_menuitems` (`name`, `module`, `label`, `plugin`, `params`, `menu`, `submenu`, `order`) VALUES
('ynmobile_admin_main_themes', 'ynmobile', 'Manage Themes','', '{"route":"admin_default","module":"ynmobile","controller":"themes"}', 'ynmobile_admin_main', '', 6);