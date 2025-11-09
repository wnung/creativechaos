# Google Sheets Sync Setup

This portal can sync **registrations** to Google Sheets using a **Service Account** (no Composer required).

## 1) Create a Service Account & key
- In Google Cloud Console, create a project (or use an existing one).
- Enable **Google Sheets API**.
- Create a **Service Account** and then **Create Key** (JSON). Download it.

## 2) Share your Sheet
- Create a Google Sheet and note its **Spreadsheet ID** (in the URL).
- Share the sheet with the service account's **client_email** as **Editor**.

## 3) Upload the JSON
- Upload the JSON file to the portal root as `service_account.json` (or set a custom path in config).

## 4) Edit config
Edit `config.php` and set:
```php
'google_sheets' => [
  'enabled' => true,
  'service_account_json' => __DIR__ . '/service_account.json',
  'sheet_id' => 'YOUR_SHEET_ID_HERE',
  'range' => 'Registrations!A1',
  'value_input_option' => 'RAW'
],
```

## 5) Column order
The portal appends rows in this order:
```
ID | Student Name | Grade | School | Guardian Email | Category | Created At
```

## 6) Troubleshooting
- If rows aren't appearing, confirm:
  - Sheets API is enabled in your GCP project.
  - The sheet is shared with the service account email.
  - Your DreamHost PHP has `openssl` enabled (it usually does).
  - Check file permissions and path to `service_account.json`.
