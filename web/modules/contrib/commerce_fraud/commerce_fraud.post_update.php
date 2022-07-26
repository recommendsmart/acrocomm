<?php

/**
 * @file
 * Post update functions for Commerce Fraud.
 */

/**
 * Remove primary key and add note field to commerce_fraud_fraud_score table.
 */
function commerce_fraud_update_7100() {
  $table = 'commerce_fraud_fraud_score';
  db_drop_primary_key($table);
  $spec = [
    'type' => 'varchar',
    'length' => 255,
  ];
  db_add_field($table, 'note', $spec);
}
