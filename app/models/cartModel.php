<?php

class cartModel
{
    public function getCart($id)
    {
        $shoppingCart = custom("
        SELECT shopping_cart.id,shopping_cart.user_id,product.id AS product_id, product.description,product.material, product_variation.color,product_variation.image,product_variation.size,shopping_cart.quantity,(product_variation.sub_price+product.price) AS price
FROM shopping_cart,product_variation,product
WHERE shopping_cart.user_id = $id
AND product.id = product_variation.product_id
AND shopping_cart.product_variation_id = product_variation.id
        ");
        $total = 0;
        $count = 0;
        foreach ($shoppingCart as $key => $val) {
            $total = $total + $val['quantity'] * $val['price'];
            $count = $count + $val['quantity'];
        }
        $res['numOfProduct'] = $count;
        $res['totalCart'] = $total;
        $res['obj'] = $shoppingCart;

        return $res;
    }
    public function getProductInCart($userID, $productID)
    {
        $condition = [
            'userID' => $userID,
            'productID' => $productID,
        ];
        $obj = selectOne('shoppingCart', $condition);


        return $obj;
    }
}