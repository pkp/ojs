----
-- ojs_schema.sql
--
-- Copyright (c) 2003-2004 The Public Knowledge Project
-- Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
--
-- OJS database schema for MySQL.
--
-- $Id$
----

-- CREATE DATABASE ojs;
-- USE ojs;


CREATE TABLE site
(
	title VARCHAR(120) NOT NULL DEFAULT 'Open Journal Systems',
	intro TEXT NOT NULL DEFAULT '',
	redirect INT NOT NULL DEFAULT 0
) TYPE=MyISAM;

CREATE TABLE journals
(
	journal_id BIGINT NOT NULL AUTO_INCREMENT,
	title VARCHAR(255) NOT NULL,
	path VARCHAR(32) NOT NULL,
	seq DECIMAL(4,1) NOT NULL,
	PRIMARY KEY(journal_id),
	UNIQUE(path)
) TYPE=MyISAM;

CREATE TABLE users
(
	user_id BIGINT NOT NULL AUTO_INCREMENT,
	username VARCHAR(32) NOT NULL,
	password VARCHAR(32) NOT NULL,
	first_name VARCHAR(40) NOT NULL DEFAULT '',
	middle_name VARCHAR(40) NOT NULL DEFAULT '',
	last_name VARCHAR(60) NOT NULL DEFAULT '',
	initials VARCHAR(5) NOT NULL DEFAULT '',
	affiliation VARCHAR(90) NOT NULL DEFAULT '',
	email VARCHAR(90) NOT NULL DEFAULT '',
	phone VARCHAR(24) NOT NULL DEFAULT '',
	fax VARCHAR(24) NOT NULL DEFAULT '',
	mailing_address VARCHAR(200) NOT NULL DEFAULT '',
	biography TEXT NOT NULL DEFAULT '',
	date_registered DATETIME NOT NULL,
	PRIMARY KEY(user_id),
	UNIQUE(username),
	UNIQUE(email)
) TYPE=MyISAM;

CREATE TABLE sessions
(
	session_id VARCHAR(32) NOT NULL,
	user_id BIGINT,
	ip_address VARCHAR(15) NOT NULL,
	created BIGINT NOT NULL DEFAULT 0,
	last_used BIGINT NOT NULL DEFAULT 0,
	remember TINYINT(1) NOT NULL DEFAULT 0,
	data TEXT NOT NULL DEFAULT '',
	PRIMARY KEY(session_id),
	INDEX(user_id),
	FOREIGN KEY(user_id) REFERENCES users(user_id)
		ON DELETE CASCADE ON UPDATE CASCADE
) TYPE=MyISAM;

CREATE TABLE roles
(
	journal_id BIGINT NOT NULL,
	user_id BIGINT NOT NULL,
	role_id BIGINT NOT NULL,
	PRIMARY KEY(journal_id, user_id, role_id),
	INDEX(journal_id),
	INDEX(user_id),
	INDEX(role_id),
	FOREIGN KEY(journal_id) REFERENCES journals(journal_id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY(user_id) REFERENCES users(user_id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY(role_id) REFERENCES roles(role_id)
		ON DELETE CASCADE ON UPDATE CASCADE
) TYPE=MyISAM;

CREATE TABLE journal_settings
(
	journal_id BIGINT NOT NULL,
	setting_name VARCHAR(255) NOT NULL,
	setting_value LONGTEXT DEFAULT NULL,
	setting_type ENUM ('bool', 'int', 'float', 'string', 'object'),
	PRIMARY KEY(journal_id, setting_name),
	FOREIGN KEY(journal_id) REFERENCES journals(journal_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE sections
(
	section_id BIGINT NOT NULL AUTO_INCREMENT,
	journal_id BIGINT NOT NULL,
	title VARCHAR(120) NOT NULL,
	abbrev VARCHAR(20) NOT NULL DEFAULT '',
	seq DECIMAL(4,1) NOT NULL,
	PRIMARY KEY(section_id),
	INDEX(journal_id)
);

CREATE TABLE section_editors
(
	journal_id BIGINT NOT NULL,
	section_id BIGINT NOT NULL,
	user_id BIGINT NOT NULL,
	PRIMARY KEY(journal_id, section_id, user_id),
	FOREIGN KEY(journal_id) REFERENCES journals(journal_id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY(section_id) REFERENCES sections(section_id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY(user_id) REFERENCES users(user_id)
		ON DELETE CASCADE ON UPDATE CASCADE
);


--
-- Insert some initial data (TEMPORARY -- should be handled by an installation script)
--

INSERT INTO site (title) VALUES ('Open Journal Systems');
INSERT INTO users (username, password) VALUES ('admin', md5('admin'));
INSERT INTO roles (journal_id, user_id, role_id) VALUES (0, 1, 1);
