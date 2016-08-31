CREATE TABLE IF NOT EXISTS `rels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `obj1` varchar(100) NOT NULL DEFAULT '',
  `obj2` varchar(100) NOT NULL DEFAULT '',
  `obj3` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`,`obj1`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

