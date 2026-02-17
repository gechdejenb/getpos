# Restock API Integration Guide (External Apps)

This guide explains how another system (e.g., Restaurant Management) can request restock from the Stock System, even when the requester has no Telegram account.

## Auth
All endpoints require `auth:api`. Use an API token in the `Authorization` header.

Example header:
```
Authorization: Bearer <TOKEN>
Content-Type: application/json
```

## Flow Summary
1. Fetch available warehouses
2. Choose `from_warehouse_id` + `to_warehouse_id`
3. Fetch products by `from_warehouse_id`
4. Create restock request (with external metadata)
5. Admin approves (Telegram inline buttons or API)
6. Destination confirms receipt (`complete`)

## Endpoints

### 1) List Warehouses
`GET /api/restock/warehouses`

Response:
```json
{
  "warehouses": [
    { "id": 1, "name": "Main Warehouse" },
    { "id": 2, "name": "Restaurant A" }
  ]
}
```

### 2) List Products By Warehouse
`GET /api/restock/warehouses/{id}/products?stock=1`

Response:
```json
{
  "products": [
    {
      "product_id": 10,
      "name": "Milk 1L",
      "product_variant_id": null,
      "qte": 55,
      "qte_purchase": 55,
      "unitPurchase": "pc",
      "purchase_unit_id": 3
    }
  ]
}
```

### 3) Create Restock Request
`POST /api/restock/requests`

Payload:
```json
{
  "from_warehouse_id": 1,
  "to_warehouse_id": 2,
  "date": "2026-02-08",
  "notes": "Urgent refill",
  "requested_by_system": "restaurant_app",
  "requested_by_name": "Restaurant A",
  "external_request_id": "RM-REQ-2341",
  "callback_url": "https://restaurant-app.example.com/restock/callback",
  "items": [
    {
      "product_id": 10,
      "product_variant_id": null,
      "purchase_unit_id": 3,
      "quantity": 5
    }
  ]
}
```

Response:
```json
{ "success": true, "request_id": 123 }
```

Telegram notifies the admin. The requester does not need Telegram.

### 4) Approve (Admin)
`POST /api/restock/requests/{id}/approve`

Creates a transfer with `statut="sent"` and deducts stock from source warehouse.

### 5) Reject
`POST /api/restock/requests/{id}/reject`

### 6) Complete (Destination)
`POST /api/restock/requests/{id}/complete`

Adds stock to destination and sets transfer to `completed`.

### 7) List Requests
`GET /api/restock/requests?status=pending`

## Callback Payload (optional)
If `callback_url` is provided, the system posts:
```json
{
  "event": "created | approved | rejected | completed",
  "request_id": 123,
  "status": "pending",
  "transfer_id": 55,
  "external_request_id": "RM-REQ-2341"
}
```

## Status Lifecycle
`pending` → `sent` → `completed`  
`pending` → `rejected`
