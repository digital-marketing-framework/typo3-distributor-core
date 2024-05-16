CREATE TABLE tx_dmfdistributorcore_domain_model_queue_job (
  label text DEFAULT '',
  hash text DEFAULT '',
  type varchar(64) DEFAULT '',

  status int(11) unsigned DEFAULT 0,
  skipped tinyint(4) unsigned DEFAULT '0' NOT NULL,
  status_message text DEFAULT '',
  serialized_data mediumtext DEFAULT '',

  changed int(11) unsigned DEFAULT '0' NOT NULL,
  created int(11) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tx_dmfdistributorcore_domain_model_api_endpoint (
	name varchar(64) DEFAULT '',
	enabled tinyint(4) unsigned DEFAULT '0' NOT NULL,
	disable_context tinyint(4) unsigned DEFAULT '0' NOT NULL,
	allow_context_override tinyint(4) unsigned DEFAULT '0' NOT NULL,
	expose_to_frontend tinyint(4) unsigned DEFAULT '0' NOT NULL,
	configuration_document text DEFAULT '',
);
