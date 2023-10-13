CREATE TABLE tx_dmfdistributorcore_domain_model_queue_job (
  label text DEFAULT '',
  hash text DEFAULT '',
  route_id varchar(64) DEFAULT '',

  status int(11) unsigned DEFAULT 0,
  skipped tinyint(4) unsigned DEFAULT '0' NOT NULL,
  status_message text DEFAULT '',
  serialized_data mediumtext DEFAULT '',

  changed int(11) unsigned DEFAULT '0' NOT NULL,
  created int(11) unsigned DEFAULT '0' NOT NULL
);
