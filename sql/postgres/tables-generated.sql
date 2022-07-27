-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/Translate/sql/tables.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE translate_sections (
  trs_page INT NOT NULL,
  trs_key TEXT NOT NULL,
  trs_text TEXT NOT NULL,
  trs_order INT DEFAULT NULL,
  PRIMARY KEY(trs_page, trs_key)
);

CREATE INDEX trs_page_order ON translate_sections (trs_page, trs_order);


CREATE TABLE revtag (
  rt_type TEXT NOT NULL, rt_page INT NOT NULL,
  rt_revision INT NOT NULL, rt_value TEXT DEFAULT NULL
);

CREATE UNIQUE INDEX rt_type_page_revision ON revtag (rt_type, rt_page, rt_revision);

CREATE INDEX rt_revision_type ON revtag (rt_revision, rt_type);


CREATE TABLE translate_groupstats (
  tgs_group TEXT NOT NULL,
  tgs_lang TEXT NOT NULL,
  tgs_total INT DEFAULT NULL,
  tgs_translated INT DEFAULT NULL,
  tgs_fuzzy INT DEFAULT NULL,
  tgs_proofread INT DEFAULT NULL,
  PRIMARY KEY(tgs_group, tgs_lang)
);

CREATE INDEX tgs_lang ON translate_groupstats (tgs_lang);


CREATE TABLE translate_reviews (
  trr_user INT NOT NULL,
  trr_page INT NOT NULL,
  trr_revision INT NOT NULL,
  PRIMARY KEY(trr_page, trr_revision, trr_user)
);


CREATE TABLE translate_groupreviews (
  tgr_group TEXT NOT NULL,
  tgr_lang TEXT NOT NULL,
  tgr_state TEXT NOT NULL,
  PRIMARY KEY(tgr_group, tgr_lang)
);


CREATE TABLE translate_tms (
  tms_sid SERIAL NOT NULL,
  tms_lang TEXT NOT NULL,
  tms_len INT NOT NULL,
  tms_text TEXT NOT NULL,
  tms_context TEXT NOT NULL,
  PRIMARY KEY(tms_sid)
);

CREATE INDEX tms_lang_len ON translate_tms (tms_lang, tms_len);


CREATE TABLE translate_tmt (
  tmt_sid INT NOT NULL, tmt_lang TEXT NOT NULL,
  tmt_text TEXT NOT NULL
);

CREATE UNIQUE INDEX tms_sid_lang ON translate_tmt (tmt_sid, tmt_lang);


CREATE TABLE translate_tmf (
  tmf_sid INT NOT NULL, tmf_text TEXT NOT NULL
);

CREATE INDEX tmf_text ON translate_tmf (tmf_text);


CREATE TABLE translate_metadata (
  tmd_group TEXT NOT NULL,
  tmd_key TEXT NOT NULL,
  tmd_value TEXT NOT NULL,
  PRIMARY KEY(tmd_group, tmd_key)
);


CREATE TABLE translate_messageindex (
  tmi_key TEXT NOT NULL,
  tmi_value TEXT NOT NULL,
  PRIMARY KEY(tmi_key)
);


CREATE TABLE translate_stash (
  ts_user INT NOT NULL,
  ts_namespace INT NOT NULL,
  ts_title TEXT NOT NULL,
  ts_value TEXT NOT NULL,
  ts_metadata TEXT NOT NULL,
  PRIMARY KEY(ts_user, ts_namespace, ts_title)
);


CREATE TABLE translate_cache (
  tc_key TEXT NOT NULL,
  tc_value TEXT DEFAULT NULL,
  tc_exptime TEXT DEFAULT NULL,
  tc_tag TEXT DEFAULT NULL,
  PRIMARY KEY(tc_key)
);

CREATE INDEX tc_tag ON translate_cache (tc_tag);
