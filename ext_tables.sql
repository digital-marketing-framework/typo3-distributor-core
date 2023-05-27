CREATE TABLE tx_digitalmarketingframeworkdistributor_domain_model_queue_job (
  label text DEFAULT '',
  hash text DEFAULT '',
  `index` int(11) unsigned DEFAULT 0,

  status int(11) unsigned DEFAULT 0,
  skipped tinyint(4) unsigned DEFAULT '0' NOT NULL,
  status_message text DEFAULT '',
  serialized_data mediumtext DEFAULT '',

  changed int(11) unsigned DEFAULT '0' NOT NULL,
  created int(11) unsigned DEFAULT '0' NOT NULL
);
