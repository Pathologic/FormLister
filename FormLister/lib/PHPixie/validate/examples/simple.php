<?php

require(__DIR__.'/vendor/autoload.php');

$validate = new \PHPixie\Validate();

$data = array(
    'name' => 'Pixie',
    'home' => 'Oak',
    'age'  => 200,
    'type' => 'fairy'
);

// There are multiple syntaxes supported
// for building the validator

// The standard approach

$validator = $validate->validator();
$document = $validator->rule()->addDocument();

$document->valueField('name')
    ->required()
    ->addFilter()
        ->alpha()
        ->minLength(3);

// You can also add filters as array
$document->valueField('home')
    ->required()
    ->addFilter()
        ->filters(array(
            'alpha',
            'minLength' => array(3)
        ));

// A shorthand approach
$document->valueField('age')
    ->required()
    ->filter('numeric');
    

// Shorthand with multiple filters
$document->valueField('type')
    ->required()
    
    // More flexible callback with
    // access to result object
    ->callback(function($result, $value) {
        if(!in_array($value, array('fairy', 'pixie'))) {
            // If does not pass add an error to the result
            $result->addMessageError("Type can be either 'fairy' or 'pixie'");
        }
    });

// By default valdiator will only allow
// fields that have rules attached to them
// If you wish to allow extra fields:
$document->allowExtraFields();

// Custom validator function
$validator->rule()->callback(function($result, $value) {
    if($value['type'] === 'fairy' && $value['home'] !== 'Oak') {
        $result->addMessageError("Fairies live only inside oaks");
    }
});


// The list of available filters can be found on:
// https://github.com/PHPixie/Validate/tree/master/src/PHPixie/Validate/Filters/Registry/Type
// Every public method in these classes is a filter

// The callback approach

$validator = $validate->validator(function($value) {
    $value->document(function($document) {
        $document
            ->allowExtraFields()
            ->field('name', function($name) {
                $name
                    ->required()
                    ->filter(function($filter) {
                        $filter
                            ->alpha()
                            ->minLength(3);
                    });
            })
            ->field('home', function($home) {
                $home
                    ->required()
                    ->filter(array(
                        'alpha',
                        'minLength' => array(3)
                    ));
            })
            ->field('age', function($age) {
                $age
                    ->required()
                    ->filter('numeric');
            })
            ->field('type', function($home) {
                $home
                    ->required()
                    ->callback(function($result, $value) {
                        if(!in_array($value, array('fairy', 'pixie'))) {
                            // If does not pass add an error to the result
                            $result->addMessageError("Type can be either 'fairy' or 'pixie'");
                        }
                    });
            });
    })
    ->callback(function($result, $value) {
        if($value['type'] === 'fairy' && $value['home'] !== 'Oak') {
            $result->addMessageError("Fairies live only inside oaks");
        }
    });
});

$result = $validator->validate($data);
var_dump($result->isValid());

// Let's cause some errors
$data['name'] = 'Pi';
$data['home'] = 'Maple';

$result = $validator->validate($data);
var_dump($result->isValid());

// print errors of the top level result
foreach($result->errors() as $error) {
    echo $error."\n";
}

foreach($result->invalidFields() as $fieldResult) {
    echo $fieldResult->path().":\n";
    foreach($fieldResult->errors() as $error) {
        echo $error."\n";
    }
}

// You can also get the value and error
// of a specific field. This is useful
// for form validation
echo "\n";
$fieldResult = $result->field('name');
var_dump($fieldResult->isValid());
var_dump($fieldResult->getValue());
foreach($fieldResult->errors() as $error) {
    echo $error."\n";
}
