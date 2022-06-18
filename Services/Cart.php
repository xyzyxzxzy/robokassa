<?php

class Cart {  
    /**
     * Список товаров корзины
     * 
     * @var array
     */
    private $productList = [];
    
    /**
     * Число бонусных рублей, примененных к корзине 
     * 
     * @var float
     */
    private $bonus = 0;
    
    /**
     * Стоимость доставки
     * 
     * @var float
     */
    private $delivery = 0;

    function __construct() {
        
        $this->productList[] = [
            'barcode' => 12331,//артикул
            'name'    => 'Плюшевый мишка',//наименование товара
            'price'   => 24,//стоимость одной единицы
            'count'   => 1//число единицы
        ];
        
        $this->productList[] = [
            'barcode' => 122,
            'name'    => 'Скалка',
            'price'   => 2452,
            'count'   => 2
        ];
        
        $this->productList[] = [
            'barcode' => 122233,
            'name'    => 'Морковь',
            'price'   => 24,
            'count'   => 5
        ];

        if (count($this->productList) > 50) {
            echo (new Exception(
                'Количество товаров в корзине не можнт быть больше 50 шт.'
            ))->getMessage();
            die;
        }
        
        $this->bonus = 333;
        $this->delivery = 250;
    }
    
    public function getProductList() : array {
        return $this->productList;
    }

    public function getProductListWithBonuses() : array {
        return $this->getProductsWithDistributedBonuses($this->bonus, $this->productList, 0);
    }

    public function getDeliveryCost() : int {
        return $this->delivery;
    }
    
    public function getCartCost() : float {
        return array_reduce($this->getProductsWithDistributedBonuses($this->bonus, $this->productList, 0), function($sum, $item){
            $price = $item['price'] * $item['count'];
            $bonus = $item['bonus'];
            return $sum + ($bonus > $price ? 0 : $price - $bonus);
        }, 0);
    }

    public function getTotalPrice() : float {
        return $this->getCartCost() + $this->getDeliveryCost();
    }

    public function getProductsWithDistributedBonuses(float $bonues, array $coefficients, int $precision) : array {
        /**
         * @var float Сумма значений всех коэффициентов
         */
        $sumCoefficients = 0.0;

        /**
         * @var float Значение максимального коэффициента по модулю
         */
        $maxCoefficient = 0.0;

        /**
         * @var mixed Ключ массива для максимального коэффициента по модулю
         */
        $maxCoefficientKey = null;

        /**
         * @var float Распределённая сумма
         */
        $allocatedAmount = 0;

        foreach ($coefficients as $keyCoefficient => $coefficient) {
            if (is_null($maxCoefficientKey)) {
                $maxCoefficientKey = $keyCoefficient;
            }

            $absCoefficient = abs($coefficient['price']);
            if ($maxCoefficient < $absCoefficient) {
                $maxCoefficient = $absCoefficient;
                $maxCoefficientKey = $keyCoefficient;
            }
            $sumCoefficients += $coefficient['price'];
        }

        if (!empty($sumCoefficients)) {
            /**
             * @var float Шаг, который прибавляем в попытках распределить сумму с учётом количества
             */
            $addStep = (0 === $precision) ? 1 : (1 / pow(10, $precision));
            foreach ($coefficients as $keyCoefficient => $coefficient) {
                /**
                 * @var boolean Флаг, удалось ли подобрать сумму распределения для текущего коэффициента
                 */
                $isOk = false;

                /**
                 * @var integer Количество попыток подобрать сумму распределения
                 */
                $i = 0;

                // Далее вычисляем сумму распределения с учётом заданного количества
                do {
                    $result = round(($bonues * $coefficient['price'] / $sumCoefficients), $precision) + $i * $addStep;
                    // Проверим распределённую сумму коэффициента относительно его количества
                    if (isset($coefficient['count']) && $coefficient['count'] > 0) {
                        if (round($result / $coefficient['count'], $precision) === ($result / $coefficient['count'])) {
                            $isOk = true;
                        }
                    } else {
                        // Количество не задано, значит не проверяем распределение по количеству
                        $isOk = true;
                    }

                    $i++;
                    if ($i > 100) {
                        echo (new Exception(
                            'Не удалось распределить сумму для коэффициента ' . $keyCoefficient
                        ))->getMessage();
                    }
                } while (!$isOk);
                
                // Присваеваем распределенные бонусы
                $coefficients[$keyCoefficient]['bonus'] = (0 === $precision) ? intval($result) : $result;
                $allocatedAmount += $result;
            }

            if ($allocatedAmount != $bonues) {
                // Распределить погрешности округления
                $tmpRes = $coefficients[$maxCoefficientKey]['bonus'] + $bonues - $allocatedAmount;
                if ($this->canDistribute($coefficients, $maxCoefficient, $tmpRes, $precision)) {
                    // Погрешности округления отнесём на коэффициент с максимальным весом
                    $coefficients[$maxCoefficientKey]['bonus'] = (0 === $precision) ? intval($tmpRes) : $tmpRes;
                } else {
                    // Погрешности округления нельзя отнести на коэффициент с максимальным весом
                    // Надо подыскать другой коэффициент
                    $isOk = false;
                    foreach ($coefficients as $keyCoefficient => $coefficient) {
                        if ($keyCoefficient != $maxCoefficientKey) {
                            $tmpRes = $coefficients[$keyCoefficient]['bonus'] + $bonues - $allocatedAmount;
                            if ($this->canDistribute($coefficients, $keyCoefficient, $tmpRes, $precision)) {
                                // Погрешности округления отнесём на коэффициент с максимальным весом
                                $coefficients[$keyCoefficient]['bonus'] = (0 === $precision) ? intval($tmpRes) : $tmpRes;
                                $isOk = true;
                                break;
                            }
                        }
                    }

                    if (!$isOk) {
                        echo (new Exception(
                            'Не удалось распределить погрешность округления'
                        ))->getMessage();
                    }
                }
            }
        }

        return $coefficients;
    }

    /**
     * Можно ли перенести погрешность на коэффициент
     * @param array $coefficients
     * @param int $keyCoefficient
     * @param int $tmpRes
     * @param int $precision
     * @return bool
     */
    private function canDistribute(array $coefficients, int $keyCoefficient, float $tmpRes, int $precision) : bool {
        return !isset($coefficients[$keyCoefficient]['count']) ||
            (isset($coefficients[$keyCoefficient]['count']) && 1 === $coefficients[$keyCoefficient]['count']) ||
            (isset($coefficients[$keyCoefficient]['count']) &&
                $coefficients[$keyCoefficient]['count'] > 0 &&
                (round($tmpRes / $coefficients[$keyCoefficient]['count'], $precision) == ($tmpRes / $coefficients[$keyCoefficient]['count']))
            );
    }
}