
Table of contents
- [What is kumite?](#what-is-kumite)
- [Installing](#installing)
- [Running tests](#running-tests)
  - [Starting tests](#starting-tests)
  - [Varying content](#varying-content)
  - [Tracking events](#tracking-events)
- [Defining tests](#defining-tests)
- [Integrating with your application](#integrating-with-your-application)
  - [Configuring](#configuring)
  - [StorageAdapter](#storageadapter)
  - [CookieAdapter](#cookieadapter)
- [Contributing](#contributing)


### What is kumite?
Kumite is a fairly simple framework for managing multivariate testing.
Tests may be defined with a number of variants and an allocation strategy that can assign a variant to a request.
Events may be tracked against variants, with optional metadata if desired.
It makes no assumptions about how your application interacts with cookies or how you would like to store test data.
It is named for the fictional fighting tournament, portrayed in the 1988 Jean-Claude Van Damme movie Bloodsport.


### Installing
The easiest way to install is via composer: `composer require lwc/kumite`


### Running tests
- [Starting tests](#starting-tests)
- [Varying content](#varying-content)
- [Tracking events](#tracking-events)

##### Starting tests
In order to run a test, you must first select a starting point for your test. Typically this will happen inside a controller action in your application.
When starting a test, you must provide an allocation method, in form of an instance that implements [Allocator](/lwc/kumite/blob/master/lib/Kumite/Allocator.php), something callable, or a constant value representing a variant.

Using a constant value:
```php
<?php

// some controller action
function productPage()
{
  // place users into variants based on a query string value. Useful for integrating with external tools.
  Kumite::start('pricing-test', $_GET['v']);
}
```

Using the Allocator interface:
```php
<?php

// defined elsewhere
class MyRandomAllocator implements Kumite\Allocator
{
  // selects a variant at random. Everyone gets tagged with a variant.
  public funciton allocate($variantKeys)
  {
    return array_rand($variantKeys);
  }
}

// some controller action
function productPage()
{
  Kumite::start('pricing-test', new MyRandomAllocator());
}
```

Using a callback:
```php
<?php

// some controller action
function productPage()
{
  $user = getActiveUser();
  
  // place users into variants based on user id. Logged out users are disqualified from participating
  Kumite::start('pricing-test', function($variantKeys) use($user) {
    if ($user)
    {
      return $variantKeys[$user->id() % count($variantKeys)]; 
    }
    return null; // user is logged out
  });
}
```

##### Varying content
There are two main ways to vary content to participants in a test, via the variant key and via variant properties:
```php

<?php if (Kumite::variant('pricing-test')->key() == 'lowerprice'): ?>
  <div>
    <p>Look at our new low price of <?php echo Kumite::variant('pricing-test')->property('price') ?></p>
  </div>
<?php endif; ?>
```

Requests that are not participating in the test will be allocated to the defined control variant for purposes of varying content, but no events will be tracked against them.

##### Tracking events
Tracking events in kumite is achieved by calling `Kumite::event($testKey, $eventKey, $metadata=array())`:
```php

function invoicePage()
{
  // ...
  $sale->save();
  // user made a purchase, track the event in kumite
  Kumite::event('pricing-test', 'sale', array('amount' => $sale->amount()));
}
```


### Defining tests
Tests are defined in the following format:

```php
<?php

$config = array(
  'pricing-test' => array(
    'active' => true, // boolean, controls if the test is active for participation
    'control' => 'control', // defines the control variant, served to request not participating in the test
    'variants' => array(
      'control', // this variant defines no properties
      'lowerprice' => array('price' => '$300') // this variant defines properties
    )
  )
);
```

### Integrating with your application

- [Configuring](#configuring)
- [StorageAdapter](#storageadapter)
- [CookieAdapter](#cookieadapter)


##### Configuring
During the initialisation of your application, you need to configure kumite.
```php
<?php

Kumite::setup(array(
  'storageAdapter' => new MyStorageAdapter(),
  'cookieAdapter' => new MyCookieAdapter(), // Optional, defaults to using PHP's $_COOKIE if not provided
  'tests' => function() {
    require_once('path/to/configuration/file.php');
    return $config;
  }
));
```

##### StorageAdapter
So that kumite can save participant and event data, you need to define a [StorageAdapter](/lwc/kumite/blob/master/lib/Kumite/Adapters/StorageAdapter.php).

Luckily, this is rather trivial, eg:
```php
<?php
class MyStorageAdapter implements Kumite\Adapters\StorageAdapter
{
  /**
	 * @return participantId
	 */
	public function createParticipant($testKey, $variantKey)
	{
		$participant = new KumiteParticipant(array(
			'test' => $testKey,
			'variant' => $variantKey
		));
		$participant->save();
		return $participant->id(); // Now we have a participant id, courtesy of the database 
	}

	public function createEvent($testKey, $variantKey, $eventKey, $participantId, $metadata=null)
	{
		$event = new KumiteEvent(array(
			'test' => $testKey,
			'variant' => $variantKey,
			'event' => $eventKey,
			'participantId' => $participantId,
			'metadata' => $metadata			
		));
		$event->save();
	}
}
```

##### CookieAdapter
If your application has a specific mechanism for interacting with cookies, you will need to define a [CookieAdapter](/lwc/kumite/blob/master/lib/Kumite/Adapters/CookieAdapter.php) so that kumite may tag requests with variant tokens.

For example, here is the source of the included default `PhpCookieAdapter`:
```php
<?php

class PhpCookieAdapter implements Kumite\Adapters\CookieAdapter
{
  public function getCookies()
	{
		return $_COOKIE;
	}

	public function getCookie($name)
	{
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
	}

	public function setCookie($name, $data)
	{
		setcookie($name, $data);
	}
}
```


### Contributing
If you've found a bug or would like to contribute, please create an issue here on GitHub, or better yet fork the project and submit a pull request!

