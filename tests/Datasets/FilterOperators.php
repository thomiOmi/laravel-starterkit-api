<?php

dataset('filterOperators', [
    'eq' => ['eq:', 'name', 'John',  '= ?'],
    'neq' => ['neq:', 'name', 'John', '!= ?'],
    'gt' => ['gt:', 'age', '18',     '> ?'],
    'gte' => ['gte:', 'age', '18',    '>= ?'],
    'lt' => ['lt:', 'age', '18',     '< ?'],
    'lte' => ['lte:', 'age', '18',    '<= ?'],
    'like' => ['like:', 'name', 'Al%', 'like ?'],
]);
