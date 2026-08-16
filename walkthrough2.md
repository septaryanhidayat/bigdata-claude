# Bug Fix Report – SmartEdu Laravel App

## Ringkasan
Audit menyeluruh dilakukan terhadap seluruh Controller, Model, dan Migration.
Ditemukan **11 bug kritis** berupa ketidaksesuaian nama kolom antara Model/Controller dan skema Database (Migration).

---

## Bug yang Ditemukan & Diperbaiki

### 1. `ChartOfAccount` Model – Field `balance` vs `current_balance`
| | |
|---|---|
| **File** | `app/Models/ChartOfAccount.php` |
| **Bug** | `$fillable` menyebut `balance`, padahal kolom di migration adalah `current_balance` |
| **Fix** | Ubah ke `current_balance` + tambahkan `$casts` float |

---

### 2. `JournalEntry` Model – Tiga nama kolom salah
| | |
|---|---|
| **File** | `app/Models/JournalEntry.php` |
| **Bug** | Pakai `coa_id` (→ `account_id`), `transaction_date` (→ `date`), `reference_no` (→ `reference_number`) |
| **Fix** | Semua field disesuaikan dengan migration, tambah `$casts` |

---

### 3. `FinanceController::paySpp()` – Tiga field salah + enum value invalid
| | |
|---|---|
| **File** | `app/Http/Controllers/Admin/FinanceController.php` |
| **Bug** | Pakai `receipt_no` (→ `receipt_number`), `payment_date` (→ `paid_at`), `payment_method = 'CASH_KASIR'` (bukan nilai enum valid), tidak update `paid_amount` di bill |
| **Fix** | Semua dikoreksi, `paid_amount` diupdate, auto-journal menggunakan field yang benar |

---

### 4. `FinanceController::storeSppBill()` – Field tidak ada di tabel
| | |
|---|---|
| **File** | `app/Http/Controllers/Admin/FinanceController.php` |
| **Bug** | Validasi pakai `month` + `year` tapi kolom DB adalah `month_period`. Tidak menyertakan `academic_year_id` & `due_date` yang wajib ada (FK constraint) |
| **Fix** | Pakai `month_period`, auto-resolve `academic_year_id` & `due_date` |

---

### 5. `MasterDataController::storeStudent()` – Field pob/dob salah
| | |
|---|---|
| **File** | `app/Http/Controllers/Admin/MasterDataController.php` |
| **Bug** | Validasi pakai `birth_place` & `birth_date`, padahal kolom di DB adalah `pob` dan `dob` |
| **Fix** | Ganti ke `pob` dan `dob` |

---

### 6. `MasterDataController::storeTeacher()` – Field type/title/position tidak ada di Employee
| | |
|---|---|
| **File** | `app/Http/Controllers/Admin/MasterDataController.php` |
| **Bug** | Validasi `type`, `title`, `position` tidak ada di tabel `employees`. Migration pakai `role_type`, `title_prefix`, `title_suffix`, `employment_status` |
| **Fix** | Semua field dikoreksi ke nama kolom yang benar |

---

### 7. `MasterDataController::teachers()/employees()/index()` – Filter `type` tidak ada
| | |
|---|---|
| **File** | `app/Http/Controllers/Admin/MasterDataController.php` |
| **Bug** | `Employee::where('type', 'GURU')` – kolom `type` tidak ada, seharusnya `role_type` |
| **Fix** | Pakai `role_type = 'TEACHER'` |

---

### 8. `MasterDataController::storeClassroom()` – FK `academic_year_id` wajib tapi tidak disertakan
| | |
|---|---|
| **File** | `app/Http/Controllers/Admin/MasterDataController.php` |
| **Bug** | Migration `classrooms` mensyaratkan `academic_year_id` (FK NOT NULL), tapi controller tidak validasi/isi field ini |
| **Fix** | Auto-resolve dari tahun akademik aktif |

---

### 9. `CanteenController::storeProduct()` – Key validasi `outlet_id` salah
| | |
|---|---|
| **File** | `app/Http/Controllers/Admin/CanteenController.php` |
| **Bug** | Validasi pakai `outlet_id` tapi kolom di `canteen_products` adalah `canteen_outlet_id` |
| **Fix** | Ubah validasi key menjadi `canteen_outlet_id` |

---

### 10. `CanteenController::checkoutPos()` – Deduct saldo yang salah
| | |
|---|---|
| **File** | `app/Http/Controllers/Admin/CanteenController.php` |
| **Bug** | Mengurangi `savings_balance` (tabungan siswa) untuk bayar kantin. Seharusnya menggunakan `canteen_balance` (saldo cashless khusus kantin) |
| **Fix** | Ganti ke `canteen_balance` |

---

### 11. `CbtPpdbController::updatePpdbStatus()` – Tiga bug sekaligus
| | |
|---|---|
| **File** | `app/Http/Controllers/Admin/CbtPpdbController.php` |
| **Bug 1** | `\App\Models\ParentModel` tidak ada di codebase → fatal error |
| **Bug 2** | `details_json` diakses sebagai array (`$reg->details_json['key']`) tapi belum di-cast di model |
| **Bug 3** | `SppBill::firstOrCreate` pakai `month_year` (tidak ada) + tidak ada `academic_year_id` + tidak ada `due_date` |
| **Fix** | Ganti `ParentModel` → `Guardian`, tambah cast `array` di model, perbaiki semua field SppBill |

---

### 12. `SavingsController::storeTransaction()` + `SavingsTransaction` Model – Banyak field mismatch
| | |
|---|---|
| **File** | `app/Http/Controllers/Admin/SavingsController.php`, `app/Models/SavingsTransaction.php` |
| **Bug** | Controller pakai `transaction_type` (→ `type`), `notes` (→ `description`), `school_id` (kolom tidak ada di tabel) |
| **Fix** | Semua field disesuaikan dengan migration |

---

## Ringkasan File yang Diubah

| File | Jenis Perubahan |
|---|---|
| `app/Models/ChartOfAccount.php` | Fix field `balance` → `current_balance` |
| `app/Models/JournalEntry.php` | Fix 3 field names salah |
| `app/Models/SavingsTransaction.php` | Fix `type`, `description` |
| `app/Models/PpdbRegistration.php` | Tambah `details_json` fillable + cast array |
| `app/Http/Controllers/Admin/FinanceController.php` | Fix paySpp + storeSppBill + storeCoa |
| `app/Http/Controllers/Admin/MasterDataController.php` | Fix storeStudent (pob/dob), storeTeacher (role_type), storeClassroom (academic_year_id), index/teachers/employees filters |
| `app/Http/Controllers/Admin/CanteenController.php` | Fix storeProduct (canteen_outlet_id), checkoutPos (canteen_balance), storeOutlet (owner_name required) |
| `app/Http/Controllers/Admin/CbtPpdbController.php` | Fix ParentModel→Guardian, details_json, SppBill fields |
| `app/Http/Controllers/Admin/SavingsController.php` | Fix transaction_type→type, notes→description, remove school_id |
| `app/Http/Controllers/Admin/CmsController.php` | Fix staffQuery filter, DB raw facade |

## Verifikasi

```
✅ php -l semua file: No syntax errors
✅ php artisan route:list: 122 routes loaded OK (exit 0)
```
