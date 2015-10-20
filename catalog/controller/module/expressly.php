<?php


use Expressly\Client;
use Expressly\Entity\MerchantType;
use Admin\CommonController;
use Expressly\Event\MerchantEvent;
use Expressly\Event\PasswordedEvent;
use Expressly\Exception\GenericException;

require_once __DIR__ . '/../../../expressly/includes.php';

/**
 *
 * 1) go to Admin Main Menu > System > Design > Layouts
 * 2) add layout "Checkout / Success" with route "checkout/success"
 * 3) go to Admin Main Menu > Extensions > Modules
 * 4) install/edit module "Expressly"
 * 5) add banner placement with layout "Checkout / Success" and position Bottom
 */
class ControllerModuleExpressly extends CommonController
{
    protected function index()
    {
        $email = $this->customer->getEmail();

        // Ignore if unauthorized
        if (!$email) return;

        $app        = $this->getApp();
        $dispatcher = $this->getDispatcher();
        $merchant   = $this->getMerchant();

        $event = new Expressly\Event\BannerEvent($merchant, $email);
        try {

            $dispatcher->dispatch(Expressly\Subscriber\BannerSubscriber::BANNER_REQUEST, $event);

            $content = $event->getContent();

            if (!$event->isSuccessful()) {
                throw new Expressly\Exception\GenericException($content['message']);
            }
        } catch (Buzz\Exception\RequestException $e) {
            $app['logger']->error(Expressly\Exception\ExceptionFormatter::format($e));
            $this->data['error_warning'] = 'We had trouble talking to the server. The server could be down; please contact expressly.';
        } catch (\Exception $e) {
            $app['logger']->error(Expressly\Exception\ExceptionFormatter::format($e));
            $this->data['error_warning'] = (string)$e->getMessage();
        }

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/expressly.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/module/expressly.tpl';
        } else {
            $this->template = 'default/template/module/expressly.tpl';
        }

        $this->data['banner'] = Expressly\Helper\BannerHelper::toHtml($event);

        $this->render();
    }
}
