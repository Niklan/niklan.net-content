<?php
/**
 * @file
 * technical_support.rules_defaults.inc
 */

/**
 * Implements hook_default_rules_configuration().
 */
function technical_support_default_rules_configuration() {
  $items = array();
  $items['rules_ticket_change_category'] = entity_import('rules_config', '{ "rules_ticket_change_category" : {
      "LABEL" : "\\u0422\\u0438\\u043a\\u0435\\u0442: \\u0421\\u043c\\u0435\\u043d\\u0430 \\u043a\\u0430\\u0442\\u0435\\u0433\\u043e\\u0440\\u0438\\u0438",
      "PLUGIN" : "reaction rule",
      "REQUIRES" : [ "rules", "comment" ],
      "ON" : [ "comment_insert" ],
      "IF" : [
        { "node_is_of_type" : {
            "node" : [ "comment:node" ],
            "type" : { "value" : { "ticket" : "ticket" } }
          }
        },
        { "entity_has_field" : { "entity" : [ "comment" ], "field" : "field_ticket_category" } },
        { "NOT data_is_empty" : { "data" : [ "comment:field-ticket-category" ] } }
      ],
      "DO" : [
        { "data_set" : {
            "data" : [ "comment:node:field-ticket-category" ],
            "value" : [ "comment:field-ticket-category" ]
          }
        }
      ]
    }
  }');
  $items['rules_ticket_change_priority'] = entity_import('rules_config', '{ "rules_ticket_change_priority" : {
      "LABEL" : "\\u0422\\u0438\\u043a\\u0435\\u0442: \\u0421\\u043c\\u0435\\u043d\\u0430 \\u043f\\u0440\\u043e\\u0440\\u0438\\u0442\\u0435\\u0442\\u0430",
      "PLUGIN" : "reaction rule",
      "REQUIRES" : [ "rules", "comment" ],
      "ON" : [ "comment_insert" ],
      "IF" : [
        { "node_is_of_type" : {
            "node" : [ "comment:node" ],
            "type" : { "value" : { "ticket" : "ticket" } }
          }
        },
        { "entity_has_field" : { "entity" : [ "comment" ], "field" : "field_ticket_priority" } },
        { "NOT data_is_empty" : { "data" : [ "comment:field-ticket-priority" ] } }
      ],
      "DO" : [
        { "data_set" : {
            "data" : [ "comment:node:field-ticket-priority" ],
            "value" : [ "comment:field-ticket-priority" ]
          }
        }
      ]
    }
  }');
  $items['rules_ticket_change_status'] = entity_import('rules_config', '{ "rules_ticket_change_status" : {
      "LABEL" : "\\u0422\\u0438\\u043a\\u0435\\u0442: \\u0421\\u043c\\u0435\\u043d\\u0430 \\u0441\\u0442\\u0430\\u0442\\u0443\\u0441\\u0430",
      "PLUGIN" : "reaction rule",
      "REQUIRES" : [ "rules", "comment" ],
      "ON" : [ "comment_insert" ],
      "IF" : [
        { "node_is_of_type" : {
            "node" : [ "comment:node" ],
            "type" : { "value" : { "ticket" : "ticket" } }
          }
        },
        { "entity_has_field" : { "entity" : [ "comment" ], "field" : "field_ticket_status" } },
        { "NOT data_is_empty" : { "data" : [ "comment:field-ticket-status" ] } }
      ],
      "DO" : [
        { "data_set" : {
            "data" : [ "comment:node:field-ticket-status" ],
            "value" : [ "comment:field-ticket-status" ]
          }
        }
      ]
    }
  }');
  $items['rules_ticket_change_title'] = entity_import('rules_config', '{ "rules_ticket_change_title" : {
      "LABEL" : "\\u0422\\u0438\\u043a\\u0435\\u0442: \\u0421\\u043c\\u0435\\u043d\\u0430 \\u0437\\u0430\\u0433\\u043e\\u043b\\u043e\\u0432\\u043a\\u0430 \\u0442\\u0438\\u043a\\u0435\\u0442\\u0430",
      "PLUGIN" : "reaction rule",
      "REQUIRES" : [ "rules" ],
      "ON" : [ "node_insert" ],
      "IF" : [
        { "node_is_of_type" : { "node" : [ "node" ], "type" : { "value" : { "ticket" : "ticket" } } } }
      ],
      "DO" : [
        { "data_set" : { "data" : [ "node:title" ], "value" : "#[node:nid]" } }
      ]
    }
  }');
  return $items;
}
