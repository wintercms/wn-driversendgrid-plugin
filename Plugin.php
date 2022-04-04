<?php namespace Winter\SendgridDriver;

use Backend;
use Backend\Models\UserRole;
use System\Classes\PluginBase;

use Winter\Storm\Mail\MailManager;

use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridTransportFactory;

/**
 * SendgridDriver Plugin Information File
 */
class Plugin extends PluginBase
{
    const MODE_SENDGRID = 'sendgrid';

    public function pluginDetails()
    {
        return [
            'name'        => 'Sendgrid driver',
            'description' => 'winter.sendgriddriver:lang.plugin_description',
            'author'      => 'Winter',
            'icon'        => 'icon-leaf'
        ];
    }

    public function register()
    {
        Event::listen('mailer.beforeRegister', function ($mailManager) {
            $settings = MailSetting::instance();
            if ($settings->send_mode === self::MODE_SENDGRID) {
                $config = App::make('config');
                $config->set('mail.mailers.sendgrid.transport', self::MODE_SENDGRID);
                $config->set('services.sendgrid.api_key', $settings->sendgrid.api_key);
            }
        });
    }

    public function boot()
    {
        MailManager::extend(function ($manager) {
            $manager->addDynamicMethod('createSendgridTransport', function (array $config) {
                $factory = new SendgridTransportFactory();

                if (!isset($config['api_key'])) {
                    $config = $this->app['config']->get('services.sendgrid', []);
                }

                return $factory->create(new Dsn(
                    'sendgrid+'.($config['scheme'] ?? 'api'),
                    $config['endpoint'] ?? 'default',
                    $config['api_key']
                ));
            });
        });

        MailSetting::extend(function ($model) {
            $model->bindEvent('model.beforeValidate', function () use ($model) {
                $model->rules['sendgrid_api_key'] = 'required_if:send_mode,' . self::MODE_SENDGRID;
            });
        });

        Event::listen('backend.form.extendFields', function ($widget) {
            if (!$widget->getController() instanceof \System\Controllers\Settings) {
                return;
            }
            if (!$widget->model instanceof MailSetting) {
                return;
            }

            $field = $widget->getField('send_mode');
            $field->options(array_merge($field->options(), [self::MODE_SENDGRID => "Sendgrid"]));

            $widget->addTabFields([
                'sendgrid_api_key' => [
                    "tab"     => "system::lang.mail.general",
                    'label'   => 'winter.sendgriddriver::lang.fields.sendgrid_api_key.label',
                    'commentAbove' => 'winter.sendgriddriver::lang.fields.sendgrid_api_key.comment',
                    'trigger' => [
                        'action'    => 'show',
                        'field'     => 'send_mode',
                        'condition' => 'value[sendgrid]'
                    ]
                ],
            ]);
        });
    }
}