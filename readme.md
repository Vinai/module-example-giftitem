# Example GiftItem

This module is a basic example implementation of a gift item module.
By default it simply adds a water bottle to the cart when the customer adds a product from the sample data **Bags** attribute set.

This module is not intended for production use, it is only example code for my presentations at [MageTestFest](https://magetestfest.com/).

## Magento 2 Total Model Kata

Check out the tag `beginning-of-kata` to start.  
Then TDD your way through the creation of the free gift item total model.

### Target `collect()` behavior:

* Subtract gift item row totals sum from subtotal
* Subtract gift item base row totals sum from base subtotal
* Set `calculation_price` of every gift item to 0
* Set `base_calculation_price` of every gift item to 0
* Call `calcRowTotal` on each item

### Suggested TDD Steps

1. Create test class  
   `\Example\GiftItem\Test\Unit\Model\Totals\GiftItemAddressTotalTest`
2. Rename namespace to `Example\GiftItem\Model\Totals`
3. Extend `\PHPUnit\Framework\TestCase`
4. Test 1: inherits abstract total model
5. Test 2: returns zero if no items are passed
6. Test 3: returns zero if non gift item is passed
7. Test 4: returns gift item row total
8. Test 5: returns sum of gift item row totals only
9. Test 6: returns sum of gift item base row totals only
10. Test 7: subtracts gift item total sums from subtotal  
    (for both base row total and row total)
11. Test 8: zeros gift item calculation_price and base_calculation_price  
    and calculates the row total
    and ignores non gift items  

(c) 2017 Vinai Kopp  
License: [BSD-3-Clause](https://opensource.org/licenses/BSD-3-Clause)

 
