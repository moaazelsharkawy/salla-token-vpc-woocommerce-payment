

Provides information on how to use the REST API endpoints to process payments and refunds with ST Virtual Cards.

Authentication  
All requests require a valid API key sent in the x-api-key header. Any request without a valid key will return a 401 or 403 error.

1. Create a Payment Transaction  
Method: POST  
Path: /st-vpc/v1/transactions  
Description: Withdraws an amount from a virtual card and credits the merchant’s wallet balance.  

Request body (JSON):  
{  
  "card_number": "1234 5678 9012 3456",  
  "cvv": "123",  
  "amount": 100.50,  
  "order_id": 9876  
}

Successful response (200):  
{  
  "transaction_id": 512,  
  "status": "frozen"  
}

Example error response (400):  
{  
  "code": "invalid_card",  
  "message": "The card is invalid or disabled.",  
  "data": { "status": 400 }  
}

2. Create a Refund Transaction  
Method: POST  
Path: /st-vpc/v1/refund  
Description: Returns an amount to the ST card and debits the merchant’s wallet.  

Request body (JSON):  
{  
  "order_id": 9876,  
  "amount": 50.00  
}

Successful response (200):  
{  
  "status": "success",  
  "refund_txn_id": 513,  
  "new_wallet_bal": 1250.75,  
  "new_card_bal": 75.50  
}

Important notes:  
- All amounts are handled with 5 decimal places of precision.  
- The WooCommerce order status is automatically updated to refunded.
