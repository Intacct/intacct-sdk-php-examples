<?php

/**
 * Copyright 2020 Sage Intacct, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"). You may not
 * use this file except in compliance with the License. You may obtain a copy
 * of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * or in the "LICENSE" file accompanying this file. This file is distributed on
 * an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

require __DIR__ . '/bootstrap.php';

use Intacct\Functions\Common\NewQuery\Query;
use Intacct\Functions\Common\NewQuery\QueryFilter\Filter;
use Intacct\Functions\Common\NewQuery\QueryFilter\OrOperator;
use Intacct\Functions\Common\NewQuery\QueryOrderBy\OrderBuilder;
use Intacct\Functions\Common\NewQuery\QuerySelect\SelectBuilder;

try {

    $filter = new OrOperator([ ( new Filter('CUSTOMERID') )->like('c%'),
                               ( new Filter('CUSTOMERID') )->like('1%') ]);

    $order = ( new OrderBuilder())->descending('CUSTOMERID')->getOrders();

    $fields = ( new SelectBuilder() )->fields([ 'CUSTOMERID', 'CUSTOMERNAME' ])
                                     ->sum('TOTALDUE')
                                     ->getFields();

    $res = ( new Query() )->select($fields)
                          ->from('ARINVOICE')
                          ->filter($filter)   // Comment out this line to see all invoices without any filtering
                          ->caseInsensitive(true)
                          ->pageSize('100')
                          ->orderBy($order);

    $logger->info('Executing query to Intacct API');
    $response = $client->execute($res);
    $result = $response->getResult();

    $json_data = json_decode(json_encode($result->getData()), 1);

    if ( $json_data && (is_array($json_data)) && (count($json_data) >= 1)) {
        echo "Success! Total number of results: " . $result->getTotalCount() . PHP_EOL . PHP_EOL;
        echo "First ARINVOICE result found: " . PHP_EOL;
        foreach ( $json_data[0] as $key => $value ) {
            echo "    '$key' => '$value'" . PHP_EOL;
        }
        echo "See the log file (logs/intacct.html) for the complete list of results." . PHP_EOL;
        echo "" . PHP_EOL;
    } else {
        echo "The query executed, but no ARINVOICE objects met the query criteria." . PHP_EOL;
        echo "Either modify the filter or comment it out from the query." . PHP_EOL;
        echo "See the log file (logs/intacct.html) for the XML request." . PHP_EOL;
    }

    $logger->debug('Query successful', [
        'Company ID' => $response->getAuthentication()->getCompanyId(),
        'User ID' => $response->getAuthentication()->getUserId(),
        'Request control ID' => $response->getControl()->getControlId(),
        'Function control ID' => $result->getControlId(),
        'Total count' => $result->getTotalCount(),
        'Data' => $json_data,
    ]);
} catch (Exception $ex) {
    $logger->error('An exception was thrown', [
        get_class($ex) => $ex->getMessage(),
    ]);
    echo get_class($ex) . ': ' . $ex->getMessage();
}