CREATE TABLE pages (
	google_docs_id varchar(255) DEFAULT NULL,
	google_docs_force_update tinyint(4) DEFAULT '0' NOT NULL,
	google_docs_content mediumtext
);
