<?php
/**
 * Сформировать ссылку для оплаты робокассы и генерации робочеков.
 
 * Чек должен быть выставлен с учетом стоимости товаров, бонусных рублей 
 * (вычитаются из основной суммы) и стоимости доставки.
 
 * Стоимость товарных позиций в чеке и сумма чека должны совпадать, т.е. нужно 
 * распределить бонусы по корзине.
 
 * Описание товара в чеке: "наименование [barcode]"
 * Число товаров в корзине до 50 шт.
 * 
 * Документация на робокассу: https://docs.robokassa.ru/
 * 
 */

class CartRobo {
    /**
     * ID магазина
     * 
     * @var string
     */
    private $mrhLogin;
    
    /**
     * Пароль 1
     * 
     * @var string
     */
    private $pass1;

    /**
     * Пароль 2
     * 
     * @var string
     */
    private $pass2;

    /**
     * Тестовый режим
     * 
     * @var bool
     */
    private $isTest;

    /**
     * Корзина
     * 
     * @var Cart
     */
    private $cart;

    /**
     * Ссыкла на форму
     * 
     * @var string
     */
    private $url = 'https://auth.robokassa.ru/Merchant/PaymentForm/FormMS.js';

    public function __construct(
        string $mrhLogin = 'chililab_test',
        string $pass1 = 'wNafa017OGSsPZq82dBT',
        string $pass2 = 'bx7b41Y0zvWW8CEzjbaQ',
        bool $isTest = true
    ) {
        $this->mrhLogin = $mrhLogin;
        $this->pass1 = $pass1;
        $this->pass2 = $pass2;
        $this->isTest = $isTest;
        $this->cart = new Cart;
    }

    private function getDescription() {
        return implode(', ', array_map(function($productItem) {
            return implode(' ', [
                $productItem['name'],
                $productItem['barcode']
            ]);
        }, $this->cart->getProductListWithBonuses()));
    }

    /**
     * Получить чек
     */
    public function getCheck(): string {
        /**
         * Итоговая сумма
         * 
         * @var string
         */
        $outSum = number_format($this->cart->getTotalPrice(), 2, '.', '');

        /**
         * @var array
         */
        $products = $this->cart->getProductListWithBonuses();
        
        if (count($products) > 0) {
            $productsList = "<ul>";
            foreach ($products as $productItem) {
                $price = $productItem['price'] * $productItem['count'];
                $totalPrice = $productItem['bonus'] > $price ? 0 : $price - $productItem['bonus'];
                $productsList .= "<li>{$productItem['name']} {$productItem['barcode']} {$productItem['count']} шт. {$totalPrice} р. </li>";
            }
            $productsList .= "<li>Доставка {$this->cart->getDeliveryCost()} р. </li>";
            $productsList .= "</ul>";
            $productsList .= "Итого с учётом бонусов и доставки: {$outSum} рублей. <br><br>";
        }

        return $productsList;
    }

    /**
     * Получить ссылку на оплату
     */
    public function getPaymentLink() : string {
        /**
         * @var integer
         */
        $invoiceID = rand(1, 99999);
        
        /**
         * Итоговая сумма
         * 
         * @var string
         */
        $outSum = number_format($this->cart->getTotalPrice(), 2, '.', '');
        
        /**
         * Параметры запроса
         * 
         * @var string
         */
        $params = http_build_query([
            'MerchantLogin' => $this->mrhLogin,
            'OutSum' => $outSum,
            'InvoiceID' => $invoiceID,
            'Description' => $this->getDescription(),
            'SignatureValue' => md5("{$this->mrhLogin}:{$outSum}:{$invoiceID}:{$this->pass1}"),
            'IsTest' => $this->isTest
        ]);

        return "<html><script language=JavaScript src='{$this->url}?{$params}'></script></html>";
    }
}
