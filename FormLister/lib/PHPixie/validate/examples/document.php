<?php

require(__DIR__.'/vendor/autoload.php');

$validate = new \PHPixie\Validate();

// Imagine you want to validate this structure
$data = array(
    'name' => 'Pixie',
    
    // 'home' is a subdocument
    'home' => array(
        'location' => 'forest',
        'name'     => 'Oak'
    ),
    
    // 'spells' is an array of subdocuments
    // of the same type
    'spells' => array(
        'charm' => array(
            'name' => 'Charm Person',
            'type' => 'illusion'
        ),
        'blast' => array(
            'name' => 'Fire Blast',
            'type' => 'evocation'
        )
    )
);

// There are two approaches to define the validator,
// you can use either builder methods or callbacks

// Builder approach

$validator = $validate->validator();
$document = $validator->rule()->addDocument();

$document->valueField('name')
    ->required()
    ->addFilter()
        ->alpha()
        ->minLength(3);

$homeDocument = $document->valueField('home')
    ->required()
    ->addDocument();

$homeDocument->valueField('location')
    ->required()
    ->addFilter()
        ->in(array('forest', 'meadow'));

$homeDocument->valueField('name')
    ->required()
    ->addFilter()
        ->alpha();

$spellsArray = $document->valueField('spells')
    ->required()
    ->addArrayOf()
    ->minCount(1);

// Validate array keys
$spellDocument = $spellsArray
    ->valueKey()
    ->filter('alpha');
        
$spellDocument = $spellsArray
    ->valueItem()
    ->addDocument();

$spellDocument->valueField('name')
    ->required()
    ->addFilter()
        ->minLength(3);

$spellDocument->valueField('type')
    ->required()
    ->addFilter()
        ->alpha();

// Or a callback approach

$validator = $validate->validator(function($value) {
    $value->document(function($document) {
        $document
            ->field('name', function($name) {
                $name
                    ->required()
                    ->filter(array(
                        'alpha',
                        'minLength' => array(3)
                    ));
            })
            ->field('home', function($home) {
                $home
                    ->required()
                    ->document(function($home) {
                        
                        $home->field('location', function($location) {
                            $location
                                ->required()
                                ->addFilter()
                                    ->in(array('forest', 'meadow'));
                            });
                        
                        $home->field('name', function($name) {
                            $name
                                ->required()
                                ->filter('alpha');
                        });
                    });
            })
            ->field('spells', function($spells) {
                $spells->required()->arrayOf(function($spells){
                    $spells
                        ->minCount(1)
                        ->key(function($key) {
                            $key->filter('alpha');
                        })
                        ->item(function($spell) {
                            $spell->required()->document(function($spell) {
                                $spell->field('name', function($name) {
                                    $name
                                        ->required()
                                        ->addFilter()
                                            ->minLength(3);
                                });
                                    
                                $spell->field('type', function($type) {
                                    $type
                                        ->required()
                                        ->filter('alpha');
                                });
                            });
                    });
                });
            });
    });
});

$result = $validator->validate($data);
var_dump($result->isValid());

// Let's intoduce a few errors:
$data['name'] = '';
$data['spells']['charm']['name'] = '1';

// Set invalid key
$data['spells'][3] = $data['spells']['charm'];

$result = $validator->validate($data);
var_dump($result->isValid());

// helper function to print nested errors
function printErrors($result) {
    foreach($result->errors() as $error) {
        echo $result->path().': '.$error."\n";
    }
    
    foreach($result->invalidFields() as $result) {
        printErrors($result);
    }
}

printErrors($result);
