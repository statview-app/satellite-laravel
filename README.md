# Statview Satellite
The package that sets up the communication channel for Statview. More information at https://statview.app.

## Requirements
- PHP 8.2+
- Laravel 11, 12 or 13

## Installation
### Composer require
```bash
composer require statview/satellite
```

### Publishing the config
```bash
php artisan vendor:publish --tag="statview-config"
```

### Adding environment variables
You can get the variable data during the project setup at Statview.
```dotenv
STATVIEW_DSN=
```

The DSN contains your endpoint, project id and API key. The package parses it automatically, so setting `STATVIEW_DSN` is all you need for a default setup.

#### Optional environment variables
```dotenv
# Override the Statview endpoint (defaults to https://statview.app)
STATVIEW_ENDPOINT=

# Disable SSL verification for outgoing calls (defaults to true)
STATVIEW_VERIFY_SSL=false

# Restrict the satellite routes to a domain
STATVIEW_DOMAIN=

# Change the route prefix (defaults to statview/satellite)
STATVIEW_PATH=
```

### Maintenance mode
You need to make an exception for Statview to access your app during maintenance mode if you want to turn off maintenance mode from your Statview panel.

Add statview to the `$except` array of your `PreventRequestsDuringMaintenance` middleware.

```php
/**
 * The URIs that should be reachable while maintenance mode is enabled.
 *
 * @var array<int, string>
 */
protected $except = [
    '/statview/*'
];
```

## Usage
### Provide data for widgets
You can register your widgets by adding them in a Service Provider.
```php
use Statview\Satellite\Statview;
use Statview\Satellite\Widgets\Widget;

public function boot()
{
    Statview::registerWidgets(function () {
        return [
            Widget::make('total_users')
                ->title('Total users')
                ->value(User::count())
                ->description('All the users since start of the project'),

            Widget::make('total_teams')
                ->title('Total teams')
                ->value(Team::count()),

            Widget::make('total_projects')
                ->title('Total projects')
                ->value(Project::count()),
        ];
    });
}
```

#### Chart widgets
Use `ChartWidget` to push chart data instead of a single value.
```php
use Statview\Satellite\Widgets\ChartWidget;

ChartWidget::make('signups')
    ->title('Signups')
    ->type('line') // Defaults to line
    ->data([
        ['label' => 'Jan', 'value' => 12],
        ['label' => 'Feb', 'value' => 30],
    ]);
```

#### Testing your widgets
You can preview the registered widgets and their resolved values from the command line.
```bash
php artisan statview:test-widgets
```

### Post messages to your timeline
Posting messages to your timeline is very easy. The Satellite package has everything built-in to start posting to your timeline.

```php
use Statview\Satellite\Statview;
use Statview\Satellite\Enums\PostType;

Statview::postToTimeline(
    title: 'Houston, we have a problem',
    body: 'There is a problem with renewing subscriptions.',
    type: PostType::Danger, // Defaults to PostType::Default
    icon: '🚨', // Expects emoji string - defaults to the icon of the given type
);
```

The available types are `PostType::Default`, `PostType::Info`, `PostType::Danger`, `PostType::Warning` and `PostType::Success`. Each type has a default icon, which is used when you don't pass one yourself.

#### Adding actions to a timeline message
You can attach actions (links) to a timeline message.

```php
use Statview\Satellite\Statview;
use Statview\Satellite\Enums\PostType;
use Statview\Satellite\Widgets\Action;

Statview::postToTimeline(
    title: 'New signup',
    body: 'A new customer just signed up.',
    type: PostType::Success,
    actions: [
        Action::make()
            ->label('View customer')
            ->icon('👤')
            ->url('https://example.com/customers/1'),
    ],
);
```

#### Tagging a timeline message
Attach one or more tags to a timeline message so you can filter your timeline by
them in Statview. Tags are reusable across events and are created automatically
the first time you use them.

```php
use Statview\Satellite\Statview;
use Statview\Satellite\Enums\PostType;

Statview::postToTimeline(
    title: 'Deploy finished',
    body: 'v1.2.3 shipped to production.',
    type: PostType::Success,
    tags: ['deploy', 'production'],
);
```

### Gauges
Increment or decrement a gauge by tag.

```php
use function Statview\gauge;

gauge()->increment('active_subscriptions');
gauge()->decrement('active_subscriptions', 2);
```

### Announcements
Fetch the announcements configured in your Statview panel.

```php
use Statview\Satellite\Statview;

$announcements = Statview::getAnnouncements();
```

## Support
Send us an email at support[at]statview.app. We are happy to help.
