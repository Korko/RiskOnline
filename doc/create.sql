#NOREF
DROP TABLE IF EXISTS mvc_actions_stack;

#REFERENCES mvc_countries, mvc_colors, mvc_members
DROP TABLE IF EXISTS mvc_actions CASCADE;
DROP TABLE IF EXISTS mvc_chatbox_messages CASCADE;
DROP TABLE IF EXISTS mvc_players CASCADE;
DROP TABLE IF EXISTS mvc_lands CASCADE;
DROP TABLE IF EXISTS mvc_games_history CASCADE;

#REFERENCES mvc_continents
DROP TABLE IF EXISTS mvc_adjacent CASCADE;
DROP TABLE IF EXISTS mvc_countries CASCADE;
DROP TABLE IF EXISTS mvc_continents CASCADE;

#REFERENCES mvc_colors
DROP TABLE IF EXISTS mvc_colors CASCADE;

#REFERENCES mvc_members
DROP TABLE IF EXISTS mvc_messages CASCADE;
DROP TABLE IF EXISTS mvc_games CASCADE;
DROP TABLE IF EXISTS mvc_sessions CASCADE;
DROP TABLE IF EXISTS mvc_members CASCADE;

CREATE TABLE mvc_members (
	m_id					INT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
	m_auth					INT(3) NOT NULL DEFAULT 1,
	m_login					VARCHAR(30) NOT NULL,
	m_password				CHAR(40) NOT NULL,
	m_salt					CHAR(5) NOT NULL,
	
	CONSTRAINT PK_MEMBER PRIMARY KEY(m_id),
	CONSTRAINT MLOGIN_UNIQUE  UNIQUE (m_login)
) TYPE=InnoDB;

CREATE TABLE mvc_sessions (
	s_id					CHAR(32) NOT NULL,
	m_id					INT(8) UNSIGNED NOT NULL,
	v_ip					INT UNSIGNED NOT NULL,
	s_date					TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
	
	CONSTRAINT PK_SESSIONS PRIMARY KEY(s_id),
	INDEX(m_id),
	CONSTRAINT FK_SESSION_MEMBER FOREIGN KEY(m_id) REFERENCES mvc_members(m_id) ON DELETE CASCADE ON UPDATE CASCADE
) TYPE=InnoDB;

/* historisation des parties */
CREATE TABLE mvc_games_history (
	g_id					INT(8) UNSIGNED NOT NULL,
	victor_id				INT(8) UNSIGNED NOT NULL,
	g_start					TIMESTAMP NOT NULL,
	g_end					TIMESTAMP NOT NULL,
	
	CONSTRAINT PK_GAMES_HISTORY PRIMARY KEY(g_id),
	INDEX(victor_id),
	CONSTRAINT FK_GAMEHISTORY_MEMBERS FOREIGN KEY(victor_id) REFERENCES mvc_members(m_id) ON UPDATE CASCADE
) TYPE=InnoDB;

CREATE TABLE mvc_games (
	g_id					INT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
	g_name					VARCHAR(50) NOT NULL DEFAULT 'Public game',
	m_id					INT(8) UNSIGNED NOT NULL,
	g_access_key				CHAR(40) NULL,
	g_start					TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	g_step					INT(8) UNSIGNED NOT NULL,
	g_resolving				INT(1) UNSIGNED NOT NULL DEFAULT 0,
	
	CONSTRAINT PK_GAMES PRIMARY KEY(g_id),
	INDEX(m_id),
	CONSTRAINT FK_GAMES_MEMBERS FOREIGN KEY(m_id) REFERENCES mvc_members(m_id) ON DELETE CASCADE ON UPDATE CASCADE
) TYPE=InnoDB;

CREATE TABLE mvc_colors (
	col_id					INT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
	col_code				CHAR(6) NOT NULL,
	col_name				VARCHAR(10) NOT NULL,
	
	CONSTRAINT COLORNAME_UNIQUE UNIQUE (col_name),
	CONSTRAINT COLORCODE_UNIQUE UNIQUE (col_code),
	CONSTRAINT PK_COLORS PRIMARY KEY(col_id)
) TYPE=InnoDB;

CREATE TABLE mvc_players (
	m_id					INT(8) UNSIGNED NOT NULL,
	g_id					INT(8) UNSIGNED NOT NULL,
	col_id					INT(8) UNSIGNED NOT NULL,
	p_ready					INT(1) UNSIGNED NOT NULL DEFAULT 0,
	
	CONSTRAINT PK_PLAYERS PRIMARY KEY(m_id, g_id),
	CONSTRAINT UNIQUE_GAMECOLOR UNIQUE(g_id, col_id),
	INDEX(m_id),
	CONSTRAINT FK_PLAYERS_MEMBER FOREIGN KEY(m_id) REFERENCES mvc_members(m_id) ON DELETE CASCADE ON UPDATE CASCADE,
	INDEX(g_id),
	CONSTRAINT FK_PLAYERS_GAME FOREIGN KEY(g_id) REFERENCES mvc_games(g_id) ON DELETE CASCADE ON UPDATE CASCADE,
	INDEX(col_id),
	CONSTRAINT FK_PLAYERS_COLOR FOREIGN KEY(col_id) REFERENCES mvc_colors(col_id) ON DELETE CASCADE ON UPDATE CASCADE
) TYPE=InnoDB;

CREATE TABLE mvc_continents (
	con_id					INT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
	con_name				VARCHAR(30) NOT NULL,
	con_bonus				INT(8) UNSIGNED NOT NULL DEFAULT 0,
	
	CONSTRAINT UNIQUE_CONTNAME UNIQUE (con_name),
	CONSTRAINT PK_CONTINENTS PRIMARY KEY(con_id)
) TYPE=InnoDB;

CREATE TABLE mvc_countries (
	cou_id					INT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
	cou_name				VARCHAR(30) NOT NULL,
	con_id					INT(8) UNSIGNED NOT NULL,
	cou_income				INT(8) UNSIGNED NOT NULL DEFAULT 0,
	cou_troops_x			INT(4) UNSIGNED NOT NULL,
	cou_troops_y			INT(4) UNSIGNED NOT NULL,
	cou_d					TEXT,
	
	CONSTRAINT UNIQUE_COUNTRYNAME UNIQUE (cou_name),	
	CONSTRAINT PK_COUNTRIES PRIMARY KEY(cou_id),
	INDEX(con_id),
	CONSTRAINT FK_COUNTRIES_CONTINENTS FOREIGN KEY(con_id) REFERENCES mvc_continents(con_id) ON DELETE CASCADE ON UPDATE CASCADE
) TYPE=InnoDB;

CREATE TABLE mvc_adjacent (
	cou_id1					INT(8) UNSIGNED NOT NULL,
	cou_id2					INT(8) UNSIGNED NOT NULL,
	
	CONSTRAINT PK_ADJACENT PRIMARY KEY(cou_id1, cou_id2),
	INDEX(cou_id2, cou_id1),
	INDEX(cou_id1),
	CONSTRAINT FK_ADJACENT_COUNTRY_1 FOREIGN KEY(cou_id1) REFERENCES mvc_countries(cou_id) ON DELETE CASCADE ON UPDATE CASCADE,
	INDEX(cou_id2),
	CONSTRAINT FK_ADJACENT_COUNTRY_2 FOREIGN KEY(cou_id2) REFERENCES mvc_countries(cou_id) ON DELETE CASCADE ON UPDATE CASCADE
) TYPE=InnoDB;

CREATE TABLE mvc_lands (
	g_id					INT(8) UNSIGNED NOT NULL,
	cou_id					INT(8) UNSIGNED NOT NULL,
	m_id					INT(8) UNSIGNED NOT NULL,
	l_strength				INT(8) UNSIGNED NOT NULL DEFAULT 1,
	
	CONSTRAINT PK_LANDS PRIMARY KEY(g_id, cou_id),
	INDEX(m_id),
	CONSTRAINT FK_LANDS_MEMBERS FOREIGN KEY(m_id) REFERENCES mvc_members(m_id) ON DELETE CASCADE ON UPDATE CASCADE,
	INDEX(g_id),
	CONSTRAINT FK_LANDS_GAMES FOREIGN KEY(g_id) REFERENCES mvc_games(g_id) ON DELETE CASCADE ON UPDATE CASCADE,
	INDEX(cou_id),
	CONSTRAINT FK_LANDS_COUNTRIES FOREIGN KEY(cou_id) REFERENCES mvc_countries(cou_id) ON DELETE CASCADE ON UPDATE CASCADE
) TYPE=InnoDB;

CREATE TABLE mvc_actions (
	a_id					INT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
	g_id					INT(8) UNSIGNED NOT NULL,
	cou_from				INT(8) UNSIGNED NOT NULL,
	cou_to					INT(8) UNSIGNED NOT NULL,
	a_strength				INT(8) UNSIGNED NOT NULL,
	a_priority				INT(1) UNSIGNED NOT NULL DEFAULT 1, 
	
	CONSTRAINT PK_ACTIONS PRIMARY KEY(a_id),
	CONSTRAINT UNIQUE_ACTIONS_GAME_FROM_TO UNIQUE(g_id, cou_from, cou_to),
	INDEX(g_id),
	CONSTRAINT FK_ACTIONS_GAMES FOREIGN KEY(g_id) REFERENCES mvc_games(g_id) ON DELETE CASCADE ON UPDATE CASCADE,
	INDEX(cou_from),
	CONSTRAINT FK_ACTIONS_COUNTRIES_FROM FOREIGN KEY(cou_from) REFERENCES mvc_countries(cou_id) ON DELETE CASCADE ON UPDATE CASCADE,
	INDEX(cou_to),
	CONSTRAINT FK_ACTIONS_COUNTRIES_TO FOREIGN KEY(cou_to) REFERENCES mvc_countries(cou_id) ON DELETE CASCADE ON UPDATE CASCADE
) TYPE=InnoDB;

CREATE TABLE mvc_actions_stack (
	g_id					INT(8) UNSIGNED NOT NULL,
	cou_from				INT(8) UNSIGNED,
	cou_to					INT(8) UNSIGNED NOT NULL,
	a_strength				INT(8) UNSIGNED NOT NULL,
	a_priority				INT(1) UNSIGNED NOT NULL DEFAULT 1,
	m_id					INT(8) UNSIGNED NOT NULL
) TYPE=MyISAM;

CREATE TABLE mvc_chatbox_messages (
	mes_id					INT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
	m_id					INT(8) UNSIGNED,
	m_name					VARCHAR(30) NOT NULL,
	mes_content				TEXT NOT NULL,
	mes_date				TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	
	PRIMARY KEY(mes_id),
	CONSTRAINT FK_MESSAGES_MEMBERS FOREIGN KEY(m_id) REFERENCES mvc_members(m_id) ON DELETE SET NULL ON UPDATE CASCADE
) TYPE=InnoDB;
