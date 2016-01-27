# Validate

PHPixie Validation library

Most PHP validation libraries share a large flaw - they only work with flat arrays
and are mostly targeting form validation. This approach is irredeemably outdated, as
when developing APIs and RESTful services we frequently have to work with request
documents with complex structures. PHPixie Validate is designed to provide an easy
approach to validating those. It requires no dependencies, so even if you are not using
PHPixie it can still come in handy in your project.

Let's start with an easy flat array example first:

```php
$validate = new \PHPixie\Validate();

//Or if you are using PHPixie
$validate = $builder->components()->validate();

// The data we will be validating
$data = array(
    'name' => 'Pixie',
    'home' => 'Oak',
    'age'  => 200,
    'type' => 'fairy'
);

// There are multiple syntaxes supported
// for building a validator
// The standard approach

$validator = $validate->validator();

// A flat file is just a document
$document = $validator->rule()->addDocument();

// A required field with filters
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
    
// Pass your own callback
$document->valueField('type')
    ->required()
    
    // More flexible callback with
    // access to result object
    ->callback(function($result, $value) {
        if(!in_array($value, array('fairy', 'pixie'))) {
            
            // If is not valid add an error to the result
            $result->addMessageError("Type can be either 'fairy' or 'pixie'");
        }
    });

// By default validator will only allow
// fields that have rules attached to them
// If you wish to allow extra fields:
$document->allowExtraFields();

// Custom validator function
$validator->rule()->callback(function($result, $value) {
    if($value['type'] === 'fairy' && $value['home'] !== 'Oak') {
        $result->addMessageError("Fairies live only inside oaks");
    }
});
```

The full list of available filters can be found [here](https://github.com/PHPixie/Validate/tree/master/src/PHPixie/Validate/Filters/Registry/Type).
Every public method in these Registry classes is an available filter

You can also build the validator using an shorthand callback syntax:

```php
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
```

Now let's try validating:

```php
$result = $validator->validate($data);
var_dump($result->isValid());

// Add some errors
$data['name'] = 'Pi';
$data['home'] = 'Maple';
$result = $validator->validate($data);
var_dump($result->isValid());

// Print errors
foreach($result->errors() as $error) {
    echo $error."\n";
}
foreach($result->invalidFields() as $fieldResult) {
    echo $fieldResult->path().":\n";
    foreach($fieldResult->errors() as $error) {
        echo $error."\n";
    }
}

/*
bool(true)
bool(false)
Fairies live only inside oaks
name:
Value did not pass filter 'minLength'
*/
```

## Working with results

As you can see above a Result object contains the errors appended to it and also
results of all fields. It may see that errors are just strings, while in fact they
are also classes that implement the magic `__toString()` method for debugging convenience.
When working with forms you'll probably want to use your own error messages instead.
To do this get the type and parameters information from the error class and format
it accordingly, e.g. :

```php
if($error->type() === 'filter') {
    if($error->filter() === 'minLength') {
       $params = $error->parameters();
       echo "Please enter at least {$params[0]} characters";
    }
}
```

This way with a simple helper class you can customize all errors for your users.

## Data structures

Now let's try a structured example:

```php
$data = array(
    'name' => 'Pixie',
    
    // 'home' is just a subdocument
    'home' => array(
        'location' => 'forest',
        'name'     => 'Oak'
    ),
    
    // 'spells' is an array of documents of a particular type
    // and a string key (also has to be validated)
    // of the same type
    'spells' => array(
        'charm' => array(
            'name' => 'Charm Person',
            'type' => 'illusion'
        ),
        'blast' => array(
            'name' => 'Fire Blast',
            'type' => 'evocation'
        ),
        // ....
    )
);

$validator = $validate->validator();
$document = $validator->rule()->addDocument();

$document->valueField('name')
    ->required()
    ->addFilter()
        ->alpha()
        ->minLength(3);

// Subdocument
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

// Array of subdocuments
$spellsArray = $document->valueField('spells')
    ->required()
    ->addArrayOf()
    ->minCount(1);

// Rule for the array key
$spellDocument = $spellsArray
    ->valueKey()
    ->filter('alpha');

// Rule for the array element
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
```

It looks much better with the alternative syntax, since it follows the structure
of your data:

```php
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
```

Now let's try using it:

```php
$result = $validator->validate($data);

var_dump($result->isValid());
//bool(true)

// Add some errors
$data['name'] = '';
$data['spells']['charm']['name'] = '1';

// Invalid key (should be string)
$data['spells'][3] = $data['spells']['blast'];

$result = $validator->validate($data);

var_dump($result->isValid());
//bool(false)

// Recursive function for error printing
function printErrors($result) {
    foreach($result->errors() as $error) {
        echo $result->path().': '.$error."\n";
    }
    
    foreach($result->invalidFields() as $result) {
        printErrors($result);
    }
}
printErrors($result);

/*
name: Value is empty
spells.charm.name: Value did not pass filter 'minLength'
spells.3: Value did not pass filter 'alpha'
*/
```

## Try it out

To fire up this demo, just run:

```
git clone https://github.com/phpixie/validate
cd validate/examples

php composer.phar install
php simple.php
php document.php
```
