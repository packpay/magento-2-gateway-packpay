<?php
namespace Packpay\Gateway\Block;

use Exception;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;


class Redirect extends \Magento\Framework\View\Element\Template
{

    protected $_checkoutSession;
    protected $_scopeConfig;
    protected $_urlBuilder;
    protected $messageManager;
    protected $redirectFactory;
    protected $catalogSession;
    protected $customer_session;
    private $token;

    /**
     * @var $order Order
     */
    protected $order;
    protected $response;


    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        Session $customer_session,
        RedirectFactory $redirectFactory,
        \Magento\Framework\App\Response\Http $response,
        Template\Context $context,
        array $data
    )
    {
        $this->customer_session=$customer_session;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_urlBuilder=$context->getUrlBuilder();
        $this->messageManager=$messageManager;
        $this->redirectFactory=$redirectFactory;
        $this->response = $response;

        parent::__construct($context, $data);

    }

    public function sendToBank()
    {
        $result =new \stdClass();
        if (!$this->getOrderId()) {
            $this->response->setRedirect($this->_urlBuilder->getUrl(''));
            return "";
        }
        $token_result = $this->refresh_token();
        if (!$token_result){
            $result->status = false;
            $result->msg ="خطا دربروزرسانی توکن";
            return $result;
        }
        $data = [
            'access_token' => $this->token,
            'amount' => $this->getOrderPrice(),
            'callback_url' => $this->getCallBackUrl(),
            'verify_on_request' => true
        ];

        $method = 'developers/bank/api/v2/purchase?' . http_build_query($data);

        $response = $this->request($method, []);
        $result->msg = $response['message'];//here error
        $result->payload=$this->getOrderId();
        $status = $response['status'];

        $reference_code = array_key_exists('reference_code', $response) ? $response['reference_code'] :-1;
        if($status=="0")
        {
            $result->url ="https://dashboard.packpay.ir/bank/purchase/send/?RefId=${reference_code}";
            $result->status = true;
        }else{
            $result->status = false;
        }

        if($result->status){
            $this->changeStatus($this->getBeforeOrderStatus());
        }else{
            $this->changeStatus(Order::STATE_CANCELED);
        }
        return $result;

    }

    public function refresh_token()
    {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->getRefreshToken()
        ];
        $method = 'oauth/token?' . http_build_query($data);
        $result = $this->request($method, []);
        if (!array_key_exists('access_token',$result)) return false;
        $this->token = $result['access_token'];
        return true;
    }


    public function getFormData($paramter)
    {
        return $this->getConfig($paramter);
    }
    public function getOrderPrice(){
        $extra=1;
        if($this->useToman()){
            $extra=10;
        }

        $order = $this->getOrder();
        $amount=$order->getGrandTotal();
        return (int) $amount*$extra;
    }

    private function getOrder(){

        return $this->_orderFactory->create()->load($this->getOrderId());
    }

    function changeStatus($status){
        $order=$this->getOrder();
        $order->setStatus($status);
        $order->save();
    }
    public function getOrderId(){
        return isset($_COOKIE['order_id'])?$_COOKIE['order_id']:false;
    }

    public function getCallBackUrl(){
        return $this->_urlBuilder->getUrl('packpay/index/callback');
    }


    private function getConfig($value){
        return $this->_scopeConfig->getValue('payment/gateway/'.$value, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getClientId()
    {
        return $this->getConfig('cid');
    }

    public function getSecretId()
    {
        return $this->getConfig('sid');
    }

    public function getRefreshToken()
    {
        return $this->getConfig('ref_tkn');
    }


    public function getBeforeOrderStatus()
    {
        return $this->getConfig('order_status');
    }
    public function getAfterOrderStatus()
    {
        return $this->getConfig('after_order_status');
    }

    public function verifySettleTransaction()
    {

        $order = $this->getOrder();
        $result = new \stdClass();

        if (!$order->getData()) {
            $result->msg = "این تراکنش قبلا اعتبار سنجی شده است.";

        } else {
            $this->refresh_token();
            $data = [
                'access_token' => $this->token,
                'reference_code' => $_GET['reference_code'],
            ];
            $method = 'developers/bank/api/v2/purchase/verify?' . http_build_query($data);
            $response = $this->request($method, [], 'POST');
            $access = $response['status'] == '0' && $response['message'] == 'successful' ? true : false;

            if ($access) {
                $result->state = true;
                $result->msg = "شماره سفارش:" . $this->getOrderId() . "<br>" . "شماره پیگیری : " . $_GET['reference_code'].
                    "<br> اطلاعات بالا را جهت پیگیری های بعدی یادداشت نمایید." . "<br>";
                $order->addStatusHistoryComment('پرداخت سفارش با موفقیت انجام شد شماره پیگیری:'.$_GET['reference_code']."\n"
                    ."وضعیت پرداخت:".$response['message']);
                $order->save();
            } else {
                $result->state = false;
                $result->msg = 'تراکنش ناموفق بود. در صورت کسر وجه، مبلغ تا 72 ساعت به حساب شما بر می گردد.'."\n"."علت: ".$response['message'];
            }
            if ($result->state) {
                $this->changeStatus($this->getAfterOrderStatus());
            } else {
                $this->changeStatus(Order::STATE_CANCELED);
            }
            //unset order id
            $this->removeOrderId();
        }
        return $result;
    }

    function removeOrderId()
    {
        setcookie("order_id", "", time() - 3600,"/");
    }


    public function request($method, $params, $type = 'POST')
    {
        try {
            $cid = $this->getClientId();
            $sid = $this->getSecretId();
            $ch = curl_init("https://dashboard.packpay.ir/" . $method);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $cid . ":" . $sid);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/json',
                )
            );
            $result = curl_exec($ch);
            return json_decode($result, true);
        } catch (Exception $ex) {
            return false;
        }
    }

//Send Data
    private function CallAPI($url, $data = false)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data)));
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
    public function useToman()
    {
        return $this->getConfig('isirt');
    }
}

