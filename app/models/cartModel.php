<?php

class cartModel
{
    function checkProduct($id)
    {
        $res = custom("
        SELECT product_variation.id
        FROM product_variation,product
        WHERE product_variation.id = $id
        AND product_variation.product_id = product.id
                ");
        return $res;
    }
    public function getCart($id)
    {
        $shoppingCart = custom("
        SELECT shopping_cart.id,shopping_cart.user_id,product.id AS product_id,product.name,product_variation.id AS product_variation_id, product.description,product.material, product_variation.color,product_variation.image,product_variation.size,shopping_cart.quantity,(product_variation.sub_price+product.price) AS price
FROM shopping_cart,product_variation,product
WHERE shopping_cart.user_id = $id
AND product.id = product_variation.product_id
AND shopping_cart.product_variation_id = product_variation.id
GROUP BY shopping_cart.product_variation_id
        ");
        $total = 0;
        foreach ($shoppingCart as $key => $val) {
            $total = $total + $val['quantity'] * $val['price'];
        }
        $res['numOfProduct'] = count($shoppingCart);
        $res['totalCart'] = $total;
        $res['obj'] = $shoppingCart;
        return $res;
    }
    public function getProductInCart($productID)
    {
        $userID = $_SESSION['user']['id'];
        $condition = [
            'user_id' => $userID,
            'product_variation_id' => $productID,
        ];
        $obj = selectOne('shopping_cart', $condition);


        return $obj;
    }
}