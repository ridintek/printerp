<?php defined('BASEPATH') or exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Cell\DataType;

class Reports extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      // $this->session->set_userdata('requested_page', $this->uri->uri_string());
      // $this->sma->md('login');

      loginPage();
    }

    $this->lang->admin_load('reports', $this->Settings->user_language);
    $this->load->library('form_validation');
    // $this->load->admin_model('reports_model');
  }

  /**
   * Balance Sheet Report
   *
   * Progress
   */
  public function balancesheet()
  {
    $startDate = ($this->input->get('start_date') ?? NULL);
    $endDate   = ($this->input->get('end_date') ?? date('Y-m-d')); // Default current date

    $billerId    = ($this->input->get('biller') ?? NULL);
    $warehouseId = ($this->input->get('warehouse') ?? NULL);

    $clause = [];
    if ($billerId)  $clause['biller_id']  = $billerId;
    if ($startDate) $clause['start_date'] = $startDate;
    $clause['end_date'] = $endDate;

    $payments = $this->site->getPayments($clause); // Global Payments. start_date & end_date

    // INVESTASI [Done]
    $invBCAAmount  = 0;
    $invBNIAmount  = 0;
    $invBRIAmount  = 0;
    $invBRI2Amount = 0;
    $invMANAmount  = 0;
    $invMAN2Amount  = 0;

    $bank = $this->site->getBank(['code' => 'B6BCALUC']);

    foreach ($payments as $payment) {
      if ($payment->bank_id == $bank->id) {
        if ($payment->type == 'received') $invBCAAmount += $payment->amount;
        if ($payment->type == 'sent')     $invBCAAmount -= $payment->amount;
      }
    }

    $bank = $this->site->getBank(['code' => 'B3BNILUC']);

    foreach ($payments as $payment) {
      if ($payment->bank_id == $bank->id) {
        if ($payment->type == 'received') $invBNIAmount += $payment->amount;
        if ($payment->type == 'sent')     $invBNIAmount -= $payment->amount;
      }
    }

    $bank = $this->site->getBank(['code' => 'B2BRILUC']);

    foreach ($payments as $payment) {
      if ($payment->bank_id == $bank->id) {
        if ($payment->type == 'received') $invBRIAmount += $payment->amount;
        if ($payment->type == 'sent')     $invBRIAmount -= $payment->amount;
      }
    }

    $bank = $this->site->getBank(['code' => 'B3BRILUC']);

    foreach ($payments as $payment) {
      if ($payment->bank_id == $bank->id) {
        if ($payment->type == 'received') $invBRI2Amount += $payment->amount;
        if ($payment->type == 'sent')     $invBRI2Amount -= $payment->amount;
      }
    }

    $bank = $this->site->getBank(['code' => 'B2MANLUC']);

    foreach ($payments as $payment) {
      if ($payment->bank_id == $bank->id) {
        if ($payment->type == 'received') $invMANAmount += $payment->amount;
        if ($payment->type == 'sent')     $invMANAmount -= $payment->amount;
      }
    }

    $bank = $this->site->getBank(['code' => 'B3MANLUC']);

    foreach ($payments as $payment) {
      if ($payment->bank_id == $bank->id) {
        if ($payment->type == 'received') $invMAN2Amount += $payment->amount;
        if ($payment->type == 'sent')     $invMAN2Amount -= $payment->amount;
      }
    }

    // CASH BANK [Done]
    $cashBank = 0;
    $cashCOH  = 0;

    foreach ($payments as $payment) {
      if ($payment->method == 'Cash') {
        if ($payment->type == 'received') $cashCOH += $payment->amount;
        if ($payment->type == 'sent')     $cashCOH -= $payment->amount;
      }

      if ($payment->method == 'Transfer' || $payment->method == 'EDC') {
        if ($payment->type == 'received') $cashBank += $payment->amount;
        if ($payment->type == 'sent')     $cashBank -= $payment->amount;
      }
    }

    // SALES [Done]
    $piutangSales = $this->site->getSales($clause);

    $salesPaid = 0;
    $salesBalance = 0;

    foreach ($payments as $payment) { // Uang pelanggan yg sdh masuk.
      if ($payment->sale_id) $salesPaid += $payment->amount;
    }

    foreach ($piutangSales as $sale) { // Uang pelanggan yg blm masuk.
      $salesBalance += $sale->balance;
    }

    // EXPENSES [Clear]
    $exCategory = [
      'K001' => 0,
      'K002' => 0,
      'K003' => 0,
      'K004' => 0,
      'K005' => 0,
      'K006' => 0,
      'K007' => 0,
      'K008' => 0,
      'K009' => 0,
      'K010' => 0,
      'K011' => 0,
      'K012' => 0,
      'K013' => 0,
      'K014' => 0,
      'K015' => 0,
      'K016' => 0,
      'K017' => 0,
      // 'K018' => 0,
      // 'K019' => 0,
      'K020' => 0,
      'K021' => 0,
      'K022' => 0,
      'K023' => 0,
      'K024' => 0,
      'K025' => 0,
      'K026' => 0,
      'K027' => 0,
      'K028' => 0,
      'K029' => 0,
      'K030' => 0,
      'K031' => 0,
      'K032' => 0,
      'K033' => 0,
      'K034' => 0,
      'K035' => 0,
      'K036' => 0,
      'K037' => 0,
      'K038' => 0,
      'K039' => 0,
      'K040' => 0,
      'K041' => 0,
      'K042' => 0
    ];

    $expenses = $this->site->getExpenses($clause);

    foreach ($expenses as $expense) {
      if ($expense->payment_status != 'paid') continue; // Paid required.

      $expenseCategory = $this->site->getExpenseCategory(['id' => $expense->category_id]);

      if ($expenseCategory->code == 'K001') $exCategory['K001'] += $expense->amount;
      if ($expenseCategory->code == 'K002') $exCategory['K002'] += $expense->amount;
      if ($expenseCategory->code == 'K003') $exCategory['K003'] += $expense->amount;
      if ($expenseCategory->code == 'K004') $exCategory['K004'] += $expense->amount;
      if ($expenseCategory->code == 'K005') $exCategory['K005'] += $expense->amount;
      if ($expenseCategory->code == 'K006') $exCategory['K006'] += $expense->amount;
      if ($expenseCategory->code == 'K007') $exCategory['K007'] += $expense->amount;
      if ($expenseCategory->code == 'K008') $exCategory['K008'] += $expense->amount;
      if ($expenseCategory->code == 'K009') $exCategory['K009'] += $expense->amount;
      if ($expenseCategory->code == 'K010') $exCategory['K010'] += $expense->amount;
      if ($expenseCategory->code == 'K011') $exCategory['K011'] += $expense->amount;
      if ($expenseCategory->code == 'K012') $exCategory['K012'] += $expense->amount;
      if ($expenseCategory->code == 'K013') $exCategory['K013'] += $expense->amount;
      if ($expenseCategory->code == 'K014') $exCategory['K014'] += $expense->amount;
      if ($expenseCategory->code == 'K015') $exCategory['K015'] += $expense->amount;
      if ($expenseCategory->code == 'K016') $exCategory['K016'] += $expense->amount;
      if ($expenseCategory->code == 'K017') $exCategory['K017'] += $expense->amount;
      // if ($expenseCategory->code == 'K018') $exCategory['K018'] += $expense->amount;
      // if ($expenseCategory->code == 'K019') $exCategory['K019'] += $expense->amount;
      if ($expenseCategory->code == 'K020') $exCategory['K020'] += $expense->amount;
      if ($expenseCategory->code == 'K021') $exCategory['K021'] += $expense->amount;
      if ($expenseCategory->code == 'K022') $exCategory['K022'] += $expense->amount;
      if ($expenseCategory->code == 'K023') $exCategory['K023'] += $expense->amount;
      if ($expenseCategory->code == 'K024') $exCategory['K024'] += $expense->amount;
      if ($expenseCategory->code == 'K025') $exCategory['K025'] += $expense->amount;
      if ($expenseCategory->code == 'K026') $exCategory['K026'] += $expense->amount;
      if ($expenseCategory->code == 'K027') $exCategory['K027'] += $expense->amount;
      if ($expenseCategory->code == 'K028') $exCategory['K028'] += $expense->amount;
      if ($expenseCategory->code == 'K029') $exCategory['K029'] += $expense->amount;
      if ($expenseCategory->code == 'K030') $exCategory['K030'] += $expense->amount;
      if ($expenseCategory->code == 'K031') $exCategory['K031'] += $expense->amount;
      if ($expenseCategory->code == 'K032') $exCategory['K032'] += $expense->amount;
      if ($expenseCategory->code == 'K033') $exCategory['K033'] += $expense->amount;
      if ($expenseCategory->code == 'K034') $exCategory['K034'] += $expense->amount;
      if ($expenseCategory->code == 'K035') $exCategory['K035'] += $expense->amount;
      if ($expenseCategory->code == 'K036') $exCategory['K036'] += $expense->amount;
      if ($expenseCategory->code == 'K037') $exCategory['K037'] += $expense->amount;
      if ($expenseCategory->code == 'K038') $exCategory['K038'] += $expense->amount;
      if ($expenseCategory->code == 'K039') $exCategory['K039'] += $expense->amount;
      if ($expenseCategory->code == 'K040') $exCategory['K040'] += $expense->amount;
      if ($expenseCategory->code == 'K041') $exCategory['K041'] += $expense->amount;
      if ($expenseCategory->code == 'K042') $exCategory['K042'] += $expense->amount;
    }

    // INCOMES [Clear]
    $inCategory = [
      'M001' => 0,
      'M002' => 0,
      'M003' => 0,
      'M004' => 0,
      'M005' => 0,
      'M006' => 0,
      'M007' => 0,
      'M008' => 0,
      'M009' => 0,
    ];

    $incomes = $this->site->getIncomes($clause);

    foreach ($incomes as $income) {
      $category = $this->site->getIncomeCategory(['id' => $income->category_id]);

      if ($category->code == 'M001') $inCategory['M001'] += $income->amount;
      if ($category->code == 'M002') $inCategory['M002'] += $income->amount;
      if ($category->code == 'M003') $inCategory['M003'] += $income->amount;
      if ($category->code == 'M004') $inCategory['M004'] += $income->amount;
      if ($category->code == 'M005') $inCategory['M005'] += $income->amount;
      if ($category->code == 'M006') $inCategory['M006'] += $income->amount;
      if ($category->code == 'M007') $inCategory['M007'] += $income->amount;
      if ($category->code == 'M008') $inCategory['M008'] += $income->amount;
      if ($category->code == 'M009') $inCategory['M009'] += $income->amount;
    }

    // PAYMENTS [Clear]
    $pembayaranVendor = 0;

    foreach ($payments as $payment) {
      if ($payment->purchase_id) $pembayaranVendor += $payment->amount;
    }

    // STOCKS [Clear]
    if ($warehouseId) $clause['warehouse_id'] = $warehouseId;

    $productStocks = getProductStockValue($clause);
    unset($clause['warehouse_id']);

    $stockValue = 0;

    foreach ($productStocks as $stock) {
      $stockValue += filterDecimal($stock->value);
    }

    // PURCHASES (warehouse)
    $clause = [];
    if ($billerId)    $clause['biller_id']    = $billerId;
    if ($warehouseId) $clause['warehouse_id'] = $warehouseId;

    $purchases = $this->site->getStockPurchases($clause);
    unset($clause);

    $hutangSupplier = 0;
    $purchaseOfBuilding = 0;
    $purchaseOfVehicle  = 0;

    foreach ($purchases as $purchase) {
      if ($purchase->balance > 0) {
        $hutangSupplier += $purchase->balance;
      }
    }

    foreach ($payments as $payment) {
      if ($payment->purchase_id) {
        $purchase = $this->site->getPurchase(['id' => $payment->purchase_id]);

        if ($purchase->category_id == 18) {
          $purchaseOfVehicle += $payment->amount;
        }
        if ($purchase->category_id == 19) {
          $purchaseOfBuilding += $payment->amount;
        }
      }
    }

    $sheet = $this->ridintek->spreadsheet();

    $sheet->loadFile(FCPATH . 'files/templates/BalanceSheet_Report.xlsx');
    $sheet->getSheetByName('Sheet1');

    $sheet->setCellValue('B1', $startDate);
    $sheet->setCellValue('C1', $endDate);

    // NERACA
    // Activa Lancar
    $sheet->setCellValue('B5', $cashBank); // Cash Bank
    $sheet->setCellValue('B6', $cashCOH); // Cash COH
    $sheet->setCellValue('B7', $stockValue); // Stok Bahan
    $sheet->setCellValue('B8', $salesBalance); // Piutang Indoprinting/Hutang pelanggan.

    // Activa Tetap
    $sheet->setCellValue('B11', $purchaseOfVehicle); // Vehicle
    $sheet->setCellValue('B12', $purchaseOfBuilding); // Building

    // Hutang
    $sheet->setCellValue('B17', $hutangSupplier); // Hutang supplier
    $sheet->setCellValue('B18', ($invBNIAmount * -1)); // Hutang Bank BNI
    $sheet->setCellValue('B19', ($invBRIAmount * -1)); // Hutang Bank BRI
    $sheet->setCellValue('B20', ($invBRI2Amount * -1)); // Hutang Bank BRI 2
    $sheet->setCellValue('B21', ($invMANAmount * -1)); // Hutang Bank Mandiri
    $sheet->setCellValue('B22', ($invMAN2Amount * -1)); // Hutang Bank Mandiri 2

    // Tambahan Modal
    $sheet->setCellValue('B24', 0); // Modal
    $sheet->setCellValue('B25', 0); // Laba

    // ARUS KAS
    // Kas Masuk
    $sheet->setCellValue('E5', $salesPaid); // Penjualan outlet (total uang diterima)
    $sheet->setCellValue('E6', 0); // Penjualan asset
    $sheet->setCellValue('E7', $inCategory['M002']); // Peminjaman dari Bank
    $sheet->setCellValue('E8', $inCategory['M003']); // Terace Rent
    $sheet->setCellValue('E9', $inCategory['M004']); // ATM Rent
    $sheet->setCellValue('E10', $inCategory['M005']); // Another Income
    $sheet->setCellValue('E11', $inCategory['M006']); // Pendapatan Baltis Inn

    // Kas Keluar
    $sheet->setCellValue('E17', $exCategory['K009']); // Salary & Wage
    $sheet->setCellValue('E18', $exCategory['K008']); // Interest Investation
    $sheet->setCellValue('E19', $exCategory['K001']); // Bank Administration
    $sheet->setCellValue('E20', $exCategory['K026']); // Biaya PPH
    $sheet->setCellValue('E21', $exCategory['K027']); // Biaya PPN
    $sheet->setCellValue('E22', $exCategory['K014']); // PLN
    $sheet->setCellValue('E23', $exCategory['K011']); // Internet & Telepon
    $sheet->setCellValue('E24', $exCategory['K017']); // PDAM
    $sheet->setCellValue('E25', $exCategory['K006']); // Office Stationery
    $sheet->setCellValue('E26', $exCategory['K002']); // Drink Water
    $sheet->setCellValue('E27', $exCategory['K007']); // BBM, Tol & Parkir
    $sheet->setCellValue('E28', $exCategory['K024']); // Maintenance of Production Machine
    $sheet->setCellValue('E29', $exCategory['K025']); // Maintenance of Finishing Machine
    $sheet->setCellValue('E30', $exCategory['K041']); // Maintenance of AC & Sparepart
    $sheet->setCellValue('E31', $exCategory['K016']); // Maintenance of Vehicle & Sparepart
    $sheet->setCellValue('E32', $exCategory['K023']); // Maintenance of Building
    $sheet->setCellValue('E33', $exCategory['K029']); // Promotion, Advertisement, SMS Blast, Wreaths
    $sheet->setCellValue('E34', $exCategory['K032']); // Outlet Rent
    $sheet->setCellValue('E35', $exCategory['K037']); // Expedition Cost
    $sheet->setCellValue('E36', $exCategory['K038']); // Import Cost (Shiping, Bank, PPH, PPN, Denda, Penumpukan, Kb2)
    $sheet->setCellValue('E37', $exCategory['K005']); // Insurance of Health and Labor
    $sheet->setCellValue('E38', $exCategory['K003']); // Insurance and Building Tax
    $sheet->setCellValue('E39', $exCategory['K004']); // Insurance and Vehicle Tax
    $sheet->setCellValue('E40', $exCategory['K010']); // Application, Hosting and Web
    $sheet->setCellValue('E41', $exCategory['K012']); // RT Fee, Security & Garbage Disposal
    $sheet->setCellValue('E42', $exCategory['K042']); // Advertising Vendor
    $sheet->setCellValue('E43', $exCategory['K033']); // CSR (Corporate Social Resposibility)
    $sheet->setCellValue('E44', $exCategory['K013']); // Another Cost
    // $sheet->setCellValue('E45', $exCategory['K018']); // Purchase of Vehicle
    // $sheet->setCellValue('E46', $exCategory['K019']); // Purchase of Land and Building
    $sheet->setCellValue('E45', $exCategory['K020']); // Purchase of Production Machine
    $sheet->setCellValue('E46', $exCategory['K022']); // Purchase of Finishing Machine
    $sheet->setCellValue('E47', $exCategory['K021']); // Purchase of Computers and Supporting Equipment
    $sheet->setCellValue('E48', $exCategory['K030']); // Purchase of Building Construction
    $sheet->setCellValue('E49', $exCategory['K040']); // Purchase of Another Investation Cost
    $sheet->setCellValue('E50', $exCategory['K028']); // Prive
    $sheet->setCellValue('E51', $pembayaranVendor); // Pembayaran hutang ke vendor.

    $name = $this->session->userdata('fullname');

    $sheet->export('PrintERP-BalanceSheet-' . date('Ymd_His') . "-($name)");
  }

  public function cohs()
  {
    $startDate = ($this->input->get('start_date') ?? date('Y-m-') . '01');
    $endDate   = ($this->input->get('end_date') ?? date('Y-m-d'));
    $whIds     = $this->input->get('warehouse');

    $sheet = $this->ridintek->spreadsheet();

    $sheet->loadFile(FCPATH . 'files/templates/COH_Report.xlsx');
    $sheet->getSheetByName('Sheet1');
    $sheet->setTitle('Summary Report');

    $A1DateGrid = [
      NULL, 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
      'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ'
    ];
    $dayName = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    $lastDate = date('j', strtotime($endDate));
    $billers = $this->site->getAllBillers();
    $pg = 20000;
    $r = 4;

    foreach ($billers as $bl) {
      if ($bl->name == 'Advertising') continue;
      if ($bl->name == 'Baltis Inn') continue;
      if ($bl->name == 'Lucretia Enterprise') continue;
      if ($bl->name == 'Online') continue; // Transfer only. No Setoran COH.
      if ($bl->name == 'Salatiga') continue; // Inactive

      $sheet->setCellValue('A' . $r, $bl->name);
      $sheet->setCellValue('C' . $r, "=COUNTIF(F{$r}:AJ{$r},\"=X\")");
      $sheet->setCellValue('D' . $r, "=IF(C{$r}=0,\$C\$1*{$pg}/LEFT(\$B\$2,SEARCH(\":\",\$B\$2)-1),-C{$r}*{$pg})");

      $mutations = $this->site->getBankMutations([
        'biller_id' => $bl->id,
        'start_date' => $startDate,
        'end_date' => $endDate
      ]);

      for ($x = 1; $x <= $lastDate; $x++) {
        $isChecked = FALSE;
        $mutationStatus = NULL;
        $dayCode = date('D', strtotime(date('Y-m-', strtotime($endDate)) . $x));
        $dayIndex = date('w', strtotime(date('Y-m-', strtotime($endDate)) . $x));

        // Monday & Thu allowed only.
        if ($dayCode != 'Mon' && $dayCode != 'Thu') continue;

        $sheet->setCellValue($A1DateGrid[$x] . '2', $dayName[$dayIndex]);

        foreach ($mutations as $mut) {
          if (date('j', strtotime($mut->date)) == $x) {
            $isChecked = TRUE;
            $mutationStatus = $mut->status;
            break;
          }
        }

        if ($isChecked) {
          switch ($mutationStatus) {
            case 'paid':
              $sheet->setCellValue($A1DateGrid[$x] . $r, 'PA');
              break;
            case 'waiting_transfer':
              $sheet->setCellValue($A1DateGrid[$x] . $r, 'WT');
              break;
            case 'expired':
              $sheet->setCellValue($A1DateGrid[$x] . $r, 'EX');
          }
        } else {
          $sheet->setCellValue($A1DateGrid[$x] . $r, 'X');
        }
      }

      $r++;
    }

    $sheet->getSheetByName('Sheet2');
    $sheet->setTitle('Mutation List');

    $mutations = $this->site->getBankMutations([
      'start_date' => $startDate,
      'end_date' => $endDate
    ]);

    $r = 2;

    foreach ($mutations as $mut) {
      $pic = $this->site->getUserByID($mut->created_by);
      $biller = $this->site->getBillerByID($mut->biller_id);

      $sheet->setCellValue('A' . $r, $mut->date);
      $sheet->setCellValue('B' . $r, $mut->reference);
      $sheet->setCellValue('C' . $r, $mut->from_bank_name);
      $sheet->setCellValue('D' . $r, $mut->to_bank_name);
      $sheet->setCellValue('E' . $r, htmlRemove($mut->note));
      $sheet->setCellValue('F' . $r, filterDecimal($mut->amount));
      $sheet->setCellValue('G' . $r, $pic->fullname);
      $sheet->setCellValue('H' . $r, lang($mut->paid_by));
      $sheet->setCellValue('I' . $r, $biller->name);
      $sheet->setCellValue('J' . $r, lang($mut->status));

      $r++;
    }

    $sheet->getSheet(0);
    $sheet->setColumnAutoWidth('A');
    $sheet->setColumnAutoWidth('D');

    $sheet->getSheet(1);
    $sheet->setColumnAutoWidth('A');
    $sheet->setColumnAutoWidth('B');
    // $sheet->setColumnAutoWidth('M');

    $sheet->getSheet(0);
    $sheet->setCellValue('A1', date('F Y', strtotime($startDate)));
    $sheet->setBold('A1');

    $name = $this->session->userdata('fullname');

    $sheet->export('PrintERP-Setoran_COH-' . date('Ymd_His') . "-($name)");
  }

  /**
   * New Sales Reports
   */
  public function sales()
  {
    ini_set('max_input_time', -1);
    set_time_limit(-1);

    if ($args = func_get_args()) {
      $method = __FUNCTION__ . '_' . $args[0];

      if (method_exists($this, $method)) {
        array_shift($args);
        return call_user_func_array([$this, $method], $args);
      }
    }

    if (is_cli()) {
      if ($args = func_get_args()) {
        $a = array_search('start_date', $args);
        $b = array_search('end_date', $args);

        if ($a >= 0) {
          $startDate  = $args[$a + 1];
        }
        if ($b >= 0) {
          $endDate    = $args[$b + 1];
        }
      }
    } else {
      $startDate    = $this->input->get('start_date') ?? date('Y-m-') . '01';
      $endDate      = $this->input->get('end_date') ?? date('Y-m-d');
    }

    $sheet = $this->ridintek->spreadsheet();

    $sheet->loadFile(FCPATH . 'files/templates/Sales_Report.xlsx');
    $sheet->getSheetByName('Sheet1');
    $sheet->setCellValue('A1', date('F Y'));

    $users = $this->site->getUsers();

    $sales = $this->site->getSales([
      'start_date'  => $startDate,
      'end_date'    => $endDate
    ]);

    $saleItems = $this->site->getSaleItems([
      'start_date' => $startDate,
      'end_date' => $endDate
    ]);

    $pg = 1000; // Penalty

    $r1 = 4; // Summary
    $r2 = 3; // Sales
    $r3 = 3; // Sale Items
    $r4 = 3; // Piutang Sales

    foreach ($users as $user) {
      if ($user->active == 0) continue; // No inactive user.
      if ($user->username == 'owner') continue; // Ignore Owner Account.
      if ($user->username == 'w2p') continue; // Ignore Web2Print Account.

      if (!$sales && !$saleItems) continue;

      $warehouse = $this->site->getWarehouseByID($user->warehouse_id ?? $this->Settings->default_warehouse);

      if ($warehouse->code == 'ADV') continue; // Ignore Advertising.
      if ($warehouse->code == 'BAL') continue; // Ignore Baltis Inn.
      if ($warehouse->code == 'LUC') continue; // Ignore Lucretai.
      if ($warehouse->code == 'IDSLOS') continue; // Ignore FUCKED IDS.
      if ($warehouse->code == 'IDSUNG') continue; // Ignore FUCKED IDS.

      $sheet->setCellValue("A{$r1}", $user->fullname);
      $sheet->setCellValue("B{$r1}", $warehouse->name);

      $overGet = 0;

      $sheet->getSheetByName('Sheet2'); // Goto sheet2.

      foreach ($sales as $sale) {
        if ($sale->created_by != $user->id) continue;
        $saleJS = getJSON($sale->json_data);

        $cashier = $this->site->getUserByID($saleJS->cashier_by ?? 0);
        $payments = $this->site->getPayments(['sale_id' => $sale->id]);
        $custGroup = $this->site->getCustomerGroupByCustomerID($sale->customer_id);
        $productionStatus = '';
        $getStatus = '';

        if ($sale->status == 'preparing') {
          if ($payments) {
            if (strtotime(date('Y-m-d H:i:s')) > strtotime('+24 hours', strtotime($payments[0]->date))) {
              $getStatus = 'over_get';
              $overGet++;
            }
          }
        } else {
          $saleJS = getJSON($sale->json_data);

          if (!empty($saleJS->waiting_production_date)) {
            if ($payments) {
              if (strtotime($saleJS->waiting_production_date) > strtotime('+24 hours', strtotime($payments[0]->date))) {
                $getStatus = 'over_get';
                $overGet++;
              }
            }
          }

          if (!empty($saleJS->est_complete_date)) {
            $completedDates = [];

            foreach ($saleItems as $saleItem) {
              if ($saleItem->sale_id != $sale->id) continue;

              $saleItemJS = getJSON($saleItem->json_data);

              $completeDates[] = ($saleItemJS->completed_at ?? $saleJS->updated_at);
            }

            $completedAt = getLongestDateTime($completedDates);

            if (isCompleted($sale->status)) {
              if (strtotime($completedAt) > strtotime($saleJS->est_complete_date)) {
                $productionStatus = 'over_due';
              }
            } else {
              if (strtotime(date('Y-m-d H:i:s')) > strtotime($saleJS->est_complete_date)) {
                $productionStatus = 'over_due';
              }
            }
          }
        }

        $sheet->setCellValue("A{$r2}", $sale->date);
        $sheet->setCellValue("B{$r2}", $sale->reference);
        $sheet->setCellValue("C{$r2}", ($payments ? $payments[0]->date : ''));
        $sheet->setCellValue("D{$r2}", lang($sale->payment_status));
        $sheet->setCellValue("E{$r2}", $sale->paid);
        $sheet->setCellValue("F{$r2}", $sale->grand_total);
        $sheet->setCellValue("G{$r2}", ($saleJS->est_complete_date ?? ''));
        $sheet->setCellValue("H{$r2}", ($saleJS->source ?? ''));
        $sheet->setCellValue("I{$r2}", ($sale->use_tb ? $sale->warehouse : ''));
        $sheet->setCellValue("J{$r2}", $sale->customer);
        $sheet->setCellValue("K{$r2}", $custGroup->name);
        $sheet->setCellValue("L{$r2}", lang($sale->status));
        $sheet->setCellValue("M{$r2}", ($saleJS->waiting_production_date ?? ''));
        $sheet->setCellValue("N{$r2}", lang($productionStatus));
        $sheet->setCellValue("O{$r2}", lang($getStatus));
        $sheet->setCellValue("P{$r2}", $sale->biller);
        $sheet->setCellValue("Q{$r2}", $user->fullname);
        $sheet->setCellValue("R{$r2}", ($cashier ? $cashier->fullname : ''));

        $r2++;
      }

      $sheet->getSheetByName('Sheet3'); // Goto sheet3.

      $overComplete = 0;

      foreach ($saleItems as $saleItem) {
        $saleItemJS = getJSON($saleItem->json_data);

        if (!isset($saleItemJS->status)) {
          die("Something wrong for sale item id {$saleItem->id}");
        }

        if (isset($saleItemJS->operator_id)) {
          if ($saleItemJS->operator_id != $user->id) continue;
        }

        $overProduction = FALSE;

        if (!empty($saleItemJS->due_date)) {
          if (isCompleted($saleItemJS->status)) {
            $completedDate = ($saleItemJS->completed_at ?? $saleItemJS->updated_at);

            if (strtotime($completedDate) > strtotime($saleItemJS->due_date)) {
              $overProduction = TRUE;
              $overComplete++;
            }
          } else {
            if (strtotime(date('Y-m-d H:i:s')) > strtotime($saleItemJS->due_date)) {
              $overProduction = TRUE;
              $overComplete++;
            }
          }
        }

        $sale = $this->site->getSaleByID($saleItem->sale_id);

        $payments = $this->site->getPayments(['sale_id' => $sale->id]);

        $sheet->setCellValue("A{$r3}", $saleItem->date);
        $sheet->setCellValue("B{$r3}", $sale->reference);
        $sheet->setCellValue("C{$r3}", $saleItem->product_code);
        $sheet->setCellValue("D{$r3}", $saleItem->product_name);
        $sheet->setCellValue("E{$r3}", $saleItem->price);
        $sheet->setCellValue("F{$r3}", $saleItem->quantity);
        $sheet->setCellValue("G{$r3}", $saleItem->finished_qty);
        $sheet->setCellValue("H{$r3}", $saleItem->subtotal);
        $sheet->setCellValue("I{$r3}", ($payments ? $payments[0]->date : ''));
        $sheet->setCellValue("J{$r3}", ($saleItemJS->due_date ?? ''));
        $sheet->setCellValue("K{$r3}", ($saleItemJS->completed_at ?? $saleItemJS->updated_at ?? ''));
        $sheet->setCellValue("L{$r3}", $sale->customer);
        $sheet->setCellValue("M{$r3}", lang($saleItemJS->status));
        $sheet->setCellValue("N{$r3}", ($overProduction ? lang('over_due') : ''));
        $sheet->setCellValue("O{$r3}", $sale->warehouse);
        $sheet->setCellValue("P{$r3}", $user->fullname);

        $r3++;
      }

      $overPayment = 0;

      // Over-Payment
      foreach ($sales as $sale) {
        $saleJS = getJSON($sale->json_data);

        if (empty($saleJS->cashier_by) || $saleJS->cashier_by != $user->id) continue;
        if ($saleJS->source == 'W2P') continue;

        $payments = $this->site->getSalePayments($sale->id);

        if ($payments) {
          foreach ($payments as $payment) {
            if ($payment->method == 'Cash') {
              $paymentDate = new DateTime($payment->date);
              $saleDate = new DateTime($sale->date);

              $hour = $saleDate->diff($paymentDate)->format('%r%h');

              if ($hour > 3) $overPayment++; // Over 3 hours then POTONG GAJI MEENN !!!
            }
          }
        }
      }
      // End Over-Payment

      $sheet->getSheetByName('Sheet1'); // Back to sheet1.

      $sheet->setCellValue("C{$r1}", $overComplete);
      $sheet->setCellValue("D{$r1}", $overGet);
      $sheet->setCellValue("E{$r1}", $overPayment);
      $sheet->setCellValue("F{$r1}", "=C{$r1}+D{$r1}+E{$r1}");
      $sheet->setCellValue("G{$r1}", "=IF(F{$r1}>0,F{$r1}*-{$pg},(\$F$1*{$pg})/(LEFT(\$B$2, SEARCH(\":\",\$B$2)-1)))");

      $r1++;
    }

    $sheet->getSheetByName('Sheet1')->setTitle('Report Summary');
    $sheet->setColumnAutoWidth('A');
    $sheet->setColumnAutoWidth('B');
    $sheet->setColumnAutoWidth('C');
    $sheet->setColumnAutoWidth('D');
    $sheet->setColumnAutoWidth('E');
    $sheet->setColumnAutoWidth('F');
    $sheet->setColumnAutoWidth('G');
    $sheet->setColumnAutoWidth('H');
    $sheet->setColumnAutoWidth('I');
    $sheet->setColumnAutoWidth('J');

    $sheet->getSheetByName('Sheet2')->setTitle('Sales Report');
    $sheet->setColumnAutoWidth('A');
    $sheet->setColumnAutoWidth('B');
    $sheet->setColumnAutoWidth('C');
    $sheet->setColumnAutoWidth('D');
    $sheet->setColumnAutoWidth('E');
    $sheet->setColumnAutoWidth('F');
    $sheet->setColumnAutoWidth('G');
    $sheet->setColumnAutoWidth('H');
    $sheet->setColumnAutoWidth('I');
    $sheet->setColumnAutoWidth('J');
    $sheet->setColumnAutoWidth('K');
    $sheet->setColumnAutoWidth('L');
    $sheet->setColumnAutoWidth('M');
    $sheet->setColumnAutoWidth('N');
    $sheet->setColumnAutoWidth('O');
    $sheet->setColumnAutoWidth('P');
    $sheet->setColumnAutoWidth('Q');

    $sheet->getSheetByName('Sheet3')->setTitle('Sale Items Report');
    $sheet->setColumnAutoWidth('A');
    $sheet->setColumnAutoWidth('B');
    $sheet->setColumnAutoWidth('C');
    $sheet->setColumnAutoWidth('D');
    $sheet->setColumnAutoWidth('E');
    $sheet->setColumnAutoWidth('F');
    $sheet->setColumnAutoWidth('G');
    $sheet->setColumnAutoWidth('H');
    $sheet->setColumnAutoWidth('I');
    $sheet->setColumnAutoWidth('J');
    $sheet->setColumnAutoWidth('K');
    $sheet->setColumnAutoWidth('L');
    $sheet->setColumnAutoWidth('M');
    $sheet->setColumnAutoWidth('N');
    $sheet->setColumnAutoWidth('O');
    $sheet->setColumnAutoWidth('P');

    $sheet->getSheet(0)->setBold('A1');

    $name = $this->session->userdata('fullname') ?? 'System';

    $r = $sheet->export('PrintERP-SalesReport-' . date('Ymd_His') . "-($name)");

    echo $r; // https://printerp.indoprinting.co.id/files/exports/file.xlsx
  }

  /**
   * Sales Piutang Report
   */
  public function sales_piutang()
  {
    $startDate = $this->input->get('start_date') ?? date('Y-m-') . '01';
    $endDate   = $this->input->get('end_date') ?? date('Y-m-d');

    $sheet = $this->ridintek->spreadsheet();

    $sheet->loadFile(FCPATH . 'files/templates/Sales_Piutang.xlsx');
    $sheet->getSheetByName('Sheet1');

    $sales = $this->site->getSales(['start_date' => $startDate, 'end_date' => $endDate]);

    $r1 = 3;

    foreach ($sales as $sale) {
      if ($sale->payment_status == 'paid') continue;

      $customer = $this->site->getCustomerByID($sale->customer_id);
      $customerGroup = $this->site->getCustomerGroupByID($customer->customer_group_id);
      $pic = $this->site->getUserByID($sale->created_by);

      $sheet->setCellValue("A{$r1}", $sale->date);
      $sheet->setCellValue("B{$r1}", $sale->reference);
      $sheet->setCellValue("C{$r1}", $sale->customer);
      $sheet->setCellValue("D{$r1}", $customerGroup->name);
      $sheet->setCellValue("E{$r1}", $sale->biller);
      $sheet->setCellValue("F{$r1}", $sale->warehouse);
      $sheet->setCellValue("G{$r1}", lang($sale->status));
      $sheet->setCellValue("H{$r1}", lang($sale->payment_status));
      $sheet->setCellValue("I{$r1}", $sale->paid);
      $sheet->setCellValue("J{$r1}", $sale->grand_total);
      $sheet->setCellValue("K{$r1}", ($sale->grand_total - $sale->paid)); // $sale->balance for privilege only no for reguler need payment.
      $sheet->setCellValue("L{$r1}", $pic->fullname);

      $r1++;
    }

    // Sales Payments Report
    $sheet->getSheetByName('Sheet2');

    $r2 = 3;

    $payments = $this->site->getPayments([
      'start_date' => $startDate, 'end_date' => $endDate, 'has' => 'sale_id'
    ]);

    foreach ($payments as $payment) {
      $bank = $this->site->getBankByID($payment->bank_id);
      $pic = $this->site->getUserByID($payment->created_by);
      $sale = $this->site->getSaleByID($payment->sale_id);

      $isOverPayment = (
        (strtotime('+3 hour', strtotime($sale->date)) < strtotime($payment->date)) &&
        ($payment->method == 'Cash')
      );

      $sheet->setCellValue("A{$r2}", $payment->date);
      $sheet->setCellValue("B{$r2}", $payment->reference);
      $sheet->setCellValue("C{$r2}", $bank->name);
      $sheet->setCellValue("D{$r2}", $payment->method);
      $sheet->setCellValue("E{$r2}", $sale->biller);
      $sheet->setCellValue("F{$r2}", $payment->amount);
      $sheet->setCellValue("G{$r2}", lang($payment->type));
      $sheet->setCellValue("H{$r2}", ($isOverPayment ? 'Yes' : 'No'));
      $sheet->setCellValue("I{$r2}", ($pic ? $pic->fullname : ''));

      $r2++;
    }

    $sheet->getSheetByName('Sheet2')->setTitle('Sales Payment Report');
    $sheet->setColumnAutoWidth('A');
    $sheet->setColumnAutoWidth('B');
    $sheet->setColumnAutoWidth('C');
    $sheet->setColumnAutoWidth('D');
    $sheet->setColumnAutoWidth('E');
    $sheet->setColumnAutoWidth('F');
    $sheet->setColumnAutoWidth('G');
    $sheet->setColumnAutoWidth('H');
    $sheet->setColumnAutoWidth('I');

    $sheet->getSheetByName('Sheet1')->setTitle('Piutang Sales Report');
    $sheet->setColumnAutoWidth('A');
    $sheet->setColumnAutoWidth('B');
    $sheet->setColumnAutoWidth('C');
    $sheet->setColumnAutoWidth('D');
    $sheet->setColumnAutoWidth('E');
    $sheet->setColumnAutoWidth('F');
    $sheet->setColumnAutoWidth('G');
    $sheet->setColumnAutoWidth('H');

    $sheet->setCellValue('A1', date('F Y', strtotime($startDate)));

    $name = $this->session->userdata('fullname');

    $sheet->export('PrintERP-SalesPiutang-' . date('Ymd_His') . "-($name)");
  }

  public function stockOpnames()
  {
    $startDate = $this->input->get('start_date') ?? date('Y-m-') . '01';
    $endDate   = $this->input->get('end_date') ?? date('Y-m-d');

    $sheet = $this->ridintek->spreadsheet();

    $sheet->loadFile(FCPATH . 'files/templates/StockOpname_Report.xlsx');
    $sheet->getSheetByName('Sheet1');

    $stockOpnames = $this->site->getstockOpnames(['start_date' => $startDate, 'end_date' => $endDate]);

    foreach ($stockOpnames as $so) {
    }
  }

  /**
   * Get daily performance report. (PROGRESS)
   */
  public function getDailyPerformanceReport()
  {
    $period = $this->input->get('period'); // 2022-11
    $xls    = ($this->input->get('xls') == 1 ? TRUE : FALSE);

    $opt = [];

    $opt['period'] = ($period ?? date('Y-m')); // Default current year and month.

    if (!$xls) { // Send to DataTables.
      $this->response(200, [
        'data' => getDailyPerformanceReport($opt) // Helper
      ]);
    } else { // Save as Excel
      $ddGrid = [
        ['F', 'G', 'H'], ['I', 'J', 'K'], ['L', 'M', 'N'], ['O', 'P', 'Q'], ['R', 'S', 'T'],
        ['U', 'V', 'W'], ['X', 'Y', 'Z'], ['AA', 'AB', 'AC'], ['AD', 'AE', 'AF'], ['AG', 'AH', 'AI'],
        ['AJ', 'AK', 'AL'], ['AM', 'AN', 'AO'], ['AP', 'AQ', 'AR'], ['AS', 'AT', 'AU'], ['AV', 'AW', 'AX'],
        ['AY', 'AZ', 'BA'], ['BB', 'BC', 'BD'], ['BE', 'BF', 'BG'], ['BH', 'BI', 'BJ'], ['BK', 'BL', 'BM'],
        ['BN', 'BO', 'BP'], ['BQ', 'BR', 'BS'], ['BT', 'BU', 'BV'], ['BW', 'BX', 'BY'], ['BZ', 'CA', 'CB'],
        ['CC', 'CD', 'CE'], ['CF', 'CG', 'CH'], ['CI', 'CJ', 'CK'], ['CL', 'CM', 'CN'], ['CO', 'CP', 'CQ'],
        ['CR', 'CS', 'CT']
      ];

      $dailyPerfData = getDailyPerformanceReport($opt);

      $excel = $this->ridintek->spreadsheet();
      $excel->loadFile(FCPATH . 'files/templates/DailyPerformance_Report.xlsx');

      $excel->setTitle('Period ' . $opt['period']);

      $r1 = 3; // 3rd row.

      foreach ($dailyPerfData as $dp) {
        $excel->setCellValue('A' . $r1, $dp['biller']);
        $excel->setCellValue('B' . $r1, $dp['target']);
        $excel->setCellValue('C' . $r1, $dp['revenue']);
        $excel->setCellValue('D' . $r1, $dp['avg_revenue']);
        $excel->setCellValue('E' . $r1, $dp['forecast']);

        $r2 = 0;
        foreach ($dp['daily_data'] as $dd) {
          $excel->setCellValue($ddGrid[$r2][0] . $r1, $dd['revenue']);
          $excel->setCellValue($ddGrid[$r2][1] . $r1, $dd['stock_value']);
          $excel->setCellValue($ddGrid[$r2][2] . $r1, $dd['piutang']);

          $r2++;
        }

        $r1++;
      }

      $name = $this->session->userdata('fullname');

      $excel->export('PrintERP-DailyPerformance-' . date('Ymd_His') . "-($name)");
    }
  }

  public function getPaymentsReport()
  {
    $this->sma->checkPermissions('payments', true);

    $users       = $this->input->get('user');
    $number      = $this->input->get('number');
    $banks       = $this->input->get('bank');
    $billers     = $this->input->get('biller');
    $payment_ref = $this->input->get('payment_ref');
    $paid_by     = $this->input->get('paid_by');
    $start_date  = $this->input->get('start_date');
    $end_date    = $this->input->get('end_date');
    $xls         = ($this->input->get('xls') == 1 ? TRUE : FALSE);

    if ($start_date) {
      $start_date = $start_date . ' 00:00:00';
      $end_date   = $end_date . ' 23:59:59';
    }

    if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
      $users[] = $this->session->userdata('user_id');
    }
    if ($this->session->userdata('biller_id')) {
      $billers[] = $this->session->userdata('biller_id');
    }
    if (!$xls) { // WEB
      $this->load->library('datatables');
      $this->datatables
        ->select("payments.date as date,
        payments.reference as payment_ref,
        users.username as pic_id,
        users.fullname as pic_name,
        billers.name as biller_name,
        banks.name as bank_name, banks.holder as acc_holder,
        banks.number as acc_number,
        payments.method as payment_method,
        payments.note as payment_note,
        payments.amount as payment_amount,
        payments.type as payment_type, payments.id as id")
        ->from('payments')
        ->join('sales', 'sales.id=payments.sale_id', 'left')
        ->join('purchases', 'purchases.id=payments.purchase_id', 'left')
        ->join('transfers', 'transfers.id=payments.transfer_id', 'left')
        ->join('users', 'users.id=payments.created_by', 'left')
        ->join('billers', 'billers.id=users.biller_id', 'left')
        ->join('banks', 'banks.id=payments.bank_id', 'left')
        ->group_by('payments.id');

      if ($users) {
        $this->datatables->group_start();
        foreach ($users as $user) {
          $this->datatables->or_where('payments.created_by', $user);
        }
        $this->datatables->group_end();
      }
      if ($number) {
        $this->datatables->like('banks.number', $number, 'both');
      }
      if ($banks) {
        $this->datatables->group_start();
        foreach ($banks as $bank) {
          $this->datatables->or_where('payments.bank_id', $bank);
        }
        $this->datatables->group_end();
      }
      if ($billers) {
        $this->datatables->group_start();
        foreach ($billers as $biller) {
          $this->datatables->or_where('banks.biller_id', $biller);
        }
        $this->datatables->group_end();
      }
      if ($paid_by) {
        $this->datatables->where('payments.method', $paid_by);
      }
      if ($payment_ref) {
        $this->datatables->like('payments.reference', $payment_ref, 'both');
      }
      if ($start_date) {
        $this->datatables->where('payments.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
      }

      echo $this->datatables->generate();
    } else if ($xls) { // EXPORT EXCEL
      $this->db
        ->select("DATE_FORMAT(payments.date, '%Y-%m-%d %T') as date,
        payments.reference as payment_ref,
        users.username as pic_id,
        users.fullname as pic_name,
        billers.name as biller_name,
        banks.name as bank_name, banks.holder as acc_holder,
        banks.number as acc_number,
        payments.method as method,
        payments.note as note,
        payments.amount as amount,
        payments.type as type")
        ->from('payments')
        ->join('sales', 'payments.sale_id=sales.id', 'left')
        ->join('purchases', 'payments.purchase_id=purchases.id', 'left')
        ->join('transfers', 'payments.transfer_id=transfers.id', 'left')
        ->join('users', 'users.id=payments.created_by', 'left')
        ->join('billers', 'users.biller_id=billers.id', 'left')
        ->join('banks', 'payments.bank_id=banks.id', 'left')
        ->order_by('payments.date desc');

      if ($users) {
        $this->db->group_start();
        foreach ($users as $user) {
          $this->db->or_where('payments.created_by', $user);
        }
        $this->db->group_end();
      }
      if ($number) {
        $this->db->like('banks.number', $number, 'both');
      }
      if ($banks) {
        $this->db->group_start();
        foreach ($banks as $bank) {
          $this->db->or_where('payments.bank_id', $bank);
        }
        $this->db->group_end();
      }
      if ($billers) {
        $this->db->group_start();
        foreach ($billers as $biller) {
          $this->db->or_where('banks.biller_id', $biller);
        }
        $this->db->group_end();
      }
      if ($paid_by) {
        $this->db->where('payments.method', $paid_by);
      }
      if ($payment_ref) {
        $this->db->like('payments.reference', $payment_ref, 'both');
      }
      if ($start_date) {
        $this->db->where('payments.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
      }

      $q = $this->db->get();
      if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
          $payments[] = $row;
        }
      } else {
        $payments = null;
      }

      if (!empty($payments)) {
        $excel = $this->ridintek->spreadsheet();
        $excel->setTitle(lang('payments_report'));
        $excel->setCellValue('A1', lang('date'));
        $excel->setCellValue('B1', lang('payment_reference'));
        $excel->setCellValue('C1', lang('pic_id'));
        $excel->setCellValue('D1', lang('pic_name'));
        $excel->setCellValue('E1', lang('biller'));
        $excel->setCellValue('F1', lang('bank_name'));
        $excel->setCellValue('G1', lang('account_holder'));
        $excel->setCellValue('H1', lang('account_no'));
        $excel->setCellValue('I1', lang('paid_by'));
        $excel->setCellValue('J1', lang('note'));
        $excel->setCellValue('K1', lang('amount_received'));
        $excel->setCellValue('L1', lang('amount_sent'));
        $excel->setCellValue('M1', lang('type'));

        $row   = 2;
        $receivedTotal = 0;
        $sentTotal = 0;

        foreach ($payments as $payment) {
          $receivedAmount = ($payment->type == 'received' ? $payment->amount : '');
          $sentAmount     = ($payment->type == 'sent'     ? $payment->amount : '');

          $excel->setCellValue('A' . $row, $this->sma->hrld($payment->date));
          $excel->setCellValue('B' . $row, $payment->payment_ref);
          $excel->setCellValue('C' . $row, $payment->pic_id);
          $excel->setCellValue('D' . $row, $payment->pic_name);
          $excel->setCellValue('E' . $row, $payment->biller_name);
          $excel->setCellValue('F' . $row, $payment->bank_name);
          $excel->setCellValue('G' . $row, $payment->acc_holder);
          $excel->setCellValue('H' . $row, $payment->acc_number, DataType::TYPE_STRING);
          $excel->setCellValue('I' . $row, lang($payment->method));
          $excel->setCellValue('J' . $row, htmlRemove($payment->note));
          $excel->setCellValue('K' . $row, $receivedAmount);
          $excel->setCellValue('L' . $row, $sentAmount);
          $excel->setCellValue('M' . $row, $payment->type);

          $receivedTotal += ($payment->type == 'received' ? $payment->amount : 0);
          $sentTotal     += ($payment->type == 'sent'     ? $payment->amount : 0);
          $row++;
        }

        $excel->setCellValue('K' . $row, $receivedTotal);
        $excel->setCellValue('L' . $row, $sentTotal);
        $excel->setCellValue('K' . ($row + 1), 'Grand Total');
        $excel->setCellValue('L' . ($row + 1), $receivedTotal - $sentTotal);

        $excel->setColumnAutoWidth('A');
        $excel->setColumnAutoWidth('B');
        $excel->setColumnAutoWidth('C');
        $excel->setColumnAutoWidth('D');
        $excel->setColumnAutoWidth('E');
        $excel->setColumnAutoWidth('F');
        $excel->setColumnAutoWidth('G');
        $excel->setColumnAutoWidth('H');
        $excel->setColumnAutoWidth('I');
        $excel->setColumnAutoWidth('J');
        $excel->setColumnAutoWidth('K');
        $excel->setColumnAutoWidth('L');
        $excel->setColumnAutoWidth('M');

        $name = $this->session->userdata('fullname');

        $excel->export('PaymentReports-' . date('Ymd_His') . "-($name)");
      }

      $this->session->set_flashdata('error', lang('nothing_found'));
      redirect($_SERVER['HTTP_REFERER']);
    }
  }

  /**
   * Get income statement report.
   * @todo Only 2 params required. start_date and end_date.
   */
  public function getIncomeStatementReport()
  {
    $biller_ids = $this->input->get('biller'); // If biller not specified, then all billers except lucretia
    $start_date = ($this->input->get('start_date') ?? NULL);
    $end_date   = ($this->input->get('end_date') ?? NULL);
    $xls        = ($this->input->get('xls') == 1 ? TRUE : FALSE);

    $opt = [];
    $lucretaiMode = FALSE;

    if ($start_date) $opt['start_date'] = $start_date;
    if ($end_date)   $opt['end_date']   = $end_date;

    if (!$start_date) $opt = getCurrentMonthPeriod(); // Default current month period if no param.

    // Convert comma delimited string to array ("2,5" => [2, 5]).
    $biller_ids = (!empty($biller_ids) && !is_array($biller_ids) ? explode(',', $biller_ids) : $biller_ids);

    if ($biller_ids) { // $biller_ids MUST BE ARRAY DATATYPE.
      $billerLucretai = $this->site->getBiller(['code' => 'LUC']);

      if (gettype($biller_ids) !== 'array' && $biller_ids == $billerLucretai->id) {
        $lucretaiMode = TRUE;
      } else if (is_array($biller_ids)) {
        foreach ($biller_ids as $biller_id) {
          if ($biller_id == $billerLucretai->id) $lucretaiMode = TRUE; // Find biller LUC
        }
      }

      $opt['biller_id'] = $biller_ids; // Array
    } else {
      $lucretaiMode = FALSE;
      $billers = $this->site->getBillers();
      $opt['biller_id'] = [];

      foreach ($billers as $biller) {
        if (!$xls) {
          if (strcasecmp($biller->code, 'LUC') == 0) continue; // Ignore Lucretia.
        }

        $opt['biller_id'][] = $biller->id;
      }
    }

    if (!$xls) { // Send to DataTables.
      sendJSON([
        'error' => 0,
        'billers' => $opt['biller_id'],
        'lucmode' => ($lucretaiMode ? 1 : 0),
        'period' => [
          'start_date' => $opt['start_date'],
          'end_date'   => $opt['end_date']
        ],
        'data' => getIncomeStatementReport($opt) // Helper
      ]);
    } else { // Save as Excel
      $incomeStatementSheet = [];

      foreach ($opt['biller_id'] as $biller_id) {
        $biller = $this->site->getBillerByID($biller_id);

        $incomeStatementSheet[] = [
          'biller' => $biller->name,
          'data' => getIncomeStatementReport([
            'biller_id'  => $biller_id,
            'start_date' => $opt['start_date'],
            'end_date'   => $opt['end_date']
          ])
        ];
      }

      $excel = $this->ridintek->spreadsheet();

      $excel->setTitle('Income Statement');
      $excel->setCellValue('A1', 'Reference');

      $r = 2;

      // Vertical Columns First.
      foreach ($incomeStatementSheet[0]['data'] as $is) {
        $excel->setCellValue('A' . $r, $is['name']);

        if (!empty($is['data']) && is_array($is['data'])) {
          foreach ($is['data'] as $subData) {
            $r++;

            $excel->setCellValue('A' . $r, "--> " . $subData['name']);
          }
        }

        $r++;
      }

      $excel->setColumnAutoWidth('A');

      $col = 66; // 66 = B

      foreach ($incomeStatementSheet as $iss) {
        $r = 2;

        $excel->setCellValue(chr($col) . ($r - 1), $iss['biller']);

        foreach ($iss['data'] as $is) {
          $excel->setCellValue(chr($col) . $r, round($is['amount']));

          if (!empty($is['data']) && is_array($is['data'])) {
            foreach ($is['data'] as $subData) {
              $r++;

              $excel->setCellValue(chr($col) . $r, round($subData['amount']));
            }
          }

          $r++;
        }

        $excel->setColumnAutoWidth(chr($col)); // B, C, D, ...

        $col++;
      }

      $name = $this->session->userdata('fullname');

      $excel->export('IncomeStatement-' . date('Ymd_His') . "-($name)");
    }
  }

  /**
   * NEW: Get inventory balance data for DataTables.
   */
  public function getInventoryBalance()
  {
    $clausesBegin = '';
    $clauses = '';

    $categoryId  = $this->input->get('category');
    $itemName    = $this->input->get('item_name');
    $startDate   = $this->input->get('start_date');
    $endDate     = $this->input->get('end_date');
    $warehouseId = $this->input->get('warehouse');

    $lucretaiMode = FALSE;
    $warehouse = $this->site->getWarehouseByID($warehouseId);

    if ($warehouse && $warehouse->code == 'LUC') {
      $lucretaiMode = TRUE;
    }

    if ($startDate) {
      $endDate = ($endDate ?? date('Y-m-d'));

      $clausesBegin .= "AND date < '{$startDate} 00:00:00'";
      $clauses .= "AND date BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59'";
    }

    if ($warehouseId) {
      if ($startDate) {
        $clausesBegin .= " AND warehouse_id = {$warehouseId}";
      }
      $clauses .= " AND warehouse_id = {$warehouseId}";
    }

    if ($categoryId) {
      if ($startDate) {
        $clausesBegin .= " AND category_id = {$categoryId}";
      }
      $clauses .= " AND category_id = {$categoryId}";
    }

    //* QUERIES
    $query = "products.id AS product_id,
      products.code AS product_code,
      products.name AS product_name,
      units.code AS product_unit,";

    //* QUERY BEGINNING
    if ($startDate) {
      $query .= "(COALESCE(stock_begin_recv.total, 0) - COALESCE(stock_begin_sent.total, 0)) AS beginning,";
    } else {
      $query .= "'0' AS beginning,";
    }

    //* QUERY INCREASE
    $query .= "COALESCE(stock_recv.total, 0) AS increase,";

    //* QUERY DECREASE
    $query .= "COALESCE(stock_sent.total, 0) AS decrease,";

    //* QUERY BALANCE
    if ($startDate) {
      $query .= "(COALESCE(stock_begin_recv.total, 0) - COALESCE(stock_begin_sent.total, 0) + COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS balance,";
    } else {
      $query .= "(COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS balance,";
    }

    //* QUERY AVG COST / MARK-ON PRICE
    if ($lucretaiMode) { // If Lucretai mode.
      $query .= "products.cost AS new_cost,";
    } else {
      $query .= "products.markon_price AS new_cost,"; // All outlet except Lucretai.
    }

    //* QUERY STOCK VALUE
    $cost = ($lucretaiMode ? 'products.cost' : 'products.markon_price');
    if ($startDate) {
      $query .= "{$cost} * (COALESCE(stock_begin_recv.total, 0) - COALESCE(stock_begin_sent.total, 0) + COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS stock_value";
    } else {
      $query .= "{$cost} * (COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS stock_value";
    }

    /* EXECUTE QUERIES */
    $this->load->library('datatables');
    $this->datatables->select($query)->from('products');

    // JOIN BEGINNING
    if ($startDate) {
      $this->datatables
        ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'received' {$clausesBegin} GROUP BY product_id) stock_begin_recv", 'stock_begin_recv.product_id = products.id', 'left')
        ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'sent' {$clausesBegin} GROUP BY product_id) stock_begin_sent", 'stock_begin_sent.product_id = products.id', 'left');
    }

    // JOIN INCREASE OR BALANCE
    $this->datatables
      ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'received' {$clauses} GROUP BY product_id) stock_recv", 'stock_recv.product_id = products.id', 'left');

    // JOIN DECREASE OR BALANCE
    $this->datatables
      ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'sent' {$clauses} GROUP BY product_id) stock_sent", 'stock_sent.product_id = products.id', 'left');

    // JOIN UNIT
    $this->datatables
      ->join('units', 'units.id=products.unit', 'left');

    if ($itemName) {
      $this->datatables
        ->group_start()
        ->like("products.code", $itemName, 'both')
        ->or_like("products.name", $itemName, 'both')
        ->group_end();
    }

    if ($categoryId) {
      $this->datatables->where("products.category_id", $categoryId);
    }

    $this->datatables
      ->where_in('products.type', ['standard']) // Standard only
      ->where_not_in('products.category_id', [2, 14, 16, 17, 18]); // Not Assets and Sub-Assets.

    // echo $this->datatables->generate(['returnCompiled' => TRUE]); die;
    echo $this->datatables->generate();
  }

  /**
   * Get Inventory Balance for Excel.
   */
  public function getInventoryBalanceReport()
  {
    $this->sma->checkUserPermissions('reports-inventory_balance', 0, ['datatables' => TRUE]);

    $begin_clauses = '';
    $clauses = '';
    $category_id  = $this->input->get('category');
    $item_name    = $this->input->get('item_name');
    $start_date   = $this->input->get('start_date');
    $end_date     = $this->input->get('end_date');
    $warehouse_id = $this->input->get('warehouse');
    $xls          = $this->input->get('xls');

    $lucretaiMode = FALSE;
    $warehouse = $this->site->getWarehouseByID($warehouse_id);

    if ($warehouse && $warehouse->code == 'LUC') {
      $lucretaiMode = TRUE;
    }

    if ($start_date) {
      $end_date = ($end_date ?? date('Y-m-d'));
      $start_date = $start_date . ' 00:00:00';
      $end_date   = $end_date . ' 23:59:59';

      $begin_clauses .= "AND date < '{$start_date}'";
      $clauses .= "AND date BETWEEN '{$start_date}' AND '{$end_date}'";
    } else {
      $current_date = date('Y-m-d') . ' 00:00:00';
      $begin_clauses .= "AND date < '{$current_date}'";
    }

    if ($warehouse_id) { // Lucretia or selected warehouse
      $begin_clauses .= " AND warehouse_id = {$warehouse_id}";
      $clauses .= " AND warehouse_id = {$warehouse_id}";
    }

    if ($category_id) {
      $begin_clauses .= " AND category_id = {$category_id}";
      $clauses .= " AND category_id = {$category_id}";
    }

    if (!$xls) { // DATATABLES
      //* QUERIES
      $query = "products.id AS product_id,
        products.code AS product_code,
        products.name AS product_name,
        units.code AS product_unit,";

      //* QUERY BEGINNING
      if ($start_date) {
        $query .= "(COALESCE(stock_begin_recv.total, 0) - COALESCE(stock_begin_sent.total, 0)) AS beginning,";
      } else {
        $query .= "'0' AS beginning,";
      }

      //* QUERY INCREASE
      $query .= "COALESCE(stock_recv.total, 0) AS increase,";

      //* QUERY DECREASE
      $query .= "COALESCE(stock_sent.total, 0) AS decrease,";

      //* QUERY BALANCE
      if ($start_date) {
        $query .= "(COALESCE(stock_begin_recv.total, 0) - COALESCE(stock_begin_sent.total, 0) + COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS balance,";
      } else {
        $query .= "(COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS balance,";
      }

      //* QUERY AVG COST / MARK-ON PRICE
      if ($lucretaiMode) { // If Lucretai mode.
        $query .= "products.cost AS new_cost,";
        // $query .= "products.avg_cost AS new_cost,";
      } else {
        $query .= "products.markon_price AS new_cost,"; // All outlet except Lucretai.
      }

      //* QUERY STOCK VALUE
      $cost = ($lucretaiMode ? 'products.cost' : 'products.markon_price');

      if ($start_date) {
        $query .= "{$cost} * (COALESCE(stock_begin_recv.total, 0) - COALESCE(stock_begin_sent.total, 0) + COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS stock_value";
      } else {
        $query .= "{$cost} * (COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS stock_value";
      }

      /* EXECUTE QUERIES */
      $this->load->library('datatables');
      $this->datatables->select($query)->from('products');

      // JOIN BEGINNING
      if ($start_date) {
        $this->datatables
          ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'received' {$begin_clauses} GROUP BY product_id) stock_begin_recv", 'stock_begin_recv.product_id = products.id', 'left')
          ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'sent' {$begin_clauses} GROUP BY product_id) stock_begin_sent", 'stock_begin_sent.product_id = products.id', 'left');
      }

      // JOIN INCREASE OR BALANCE
      $this->datatables
        ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'received' {$clauses} GROUP BY product_id) stock_recv", 'stock_recv.product_id = products.id', 'left');

      // JOIN DECREASE OR BALANCE
      $this->datatables
        ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'sent' {$clauses} GROUP BY product_id) stock_sent", 'stock_sent.product_id = products.id', 'left');

      // JOIN UNIT
      $this->datatables
        ->join('units', 'units.id=products.unit', 'left');

      if ($item_name) {
        $this->datatables
          ->group_start()
          ->like("products.code", $item_name, 'both')
          ->or_like("products.name", $item_name, 'both')
          ->group_end();
      }

      if ($category_id) {
        $this->datatables->where("products.category_id", $category_id);
      }

      $this->datatables
        ->where_in('products.type', ['standard'])
        ->where_not_in('products.category_id', [2, 14, 16, 17, 18]); // Not Assets and Sub-Assets.

      echo $this->datatables->generate();
    } else if ($xls == 1) { // Export Item Details.
      //* QUERIES
      $query = "products.id AS product_id,
        products.code AS product_code,
        products.name AS product_name,
        units.code AS product_unit,
        categories.name AS category_name, products.type AS product_type, products.iuse_type AS iuse_type,";

      //* QUERY BEGINNING
      if ($start_date) {
        $query .= "(COALESCE(stock_begin_recv.total, 0) - COALESCE(stock_begin_sent.total, 0)) AS beginning,";
      } else {
        $query .= "'0' AS beginning,";
      }

      //* QUERY INCREASE
      $query .= "COALESCE(stock_recv.total, 0) AS increase,";

      //* QUERY DECREASE
      $query .= "COALESCE(stock_sent.total, 0) AS decrease,";

      //* QUERY BALANCE
      if ($start_date) {
        $query .= "(COALESCE(stock_begin_recv.total, 0) - COALESCE(stock_begin_sent.total, 0) + COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS balance,";
      } else {
        $query .= "(COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS balance,";
      }

      //* QUERY AVG COST / MARK-ON PRICE
      if ($lucretaiMode) { // If Lucretai mode.
        $query .= "products.cost AS new_cost,";
        // $query .= "products.avg_cost AS new_cost,";
      } else {
        $query .= "products.markon_price AS new_cost,"; // All outlet except Lucretai.
      }

      //* QUERY STOCK VALUE
      $cost = ($lucretaiMode ? 'products.cost' : 'products.markon_price');

      if ($start_date) {
        $query .= "{$cost} * (COALESCE(stock_begin_recv.total, 0) - COALESCE(stock_begin_sent.total, 0) + COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS stock_value";
      } else {
        $query .= "{$cost} * (COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS stock_value";
      }
      // }

      /* EXECUTE QUERIES */
      $this->db->select($query)->from('products');

      // JOIN BEGINNING
      if ($start_date) {
        $this->db
          ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'received' {$begin_clauses} GROUP BY product_id) stock_begin_recv", 'stock_begin_recv.product_id = products.id', 'left')
          ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'sent' {$begin_clauses} GROUP BY product_id) stock_begin_sent", 'stock_begin_sent.product_id = products.id', 'left');
      }

      // JOIN INCREASE OR BALANCE
      $this->db
        ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'received' {$clauses} GROUP BY product_id) stock_recv", 'stock_recv.product_id = products.id', 'left');

      // JOIN DECREASE OR BALANCE
      $this->db
        ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'sent' {$clauses} GROUP BY product_id) stock_sent", 'stock_sent.product_id = products.id', 'left');

      // JOIN UNIT
      $this->db
        ->join('units', 'units.id=products.unit', 'left');

      // JOIN CATEGORY
      $this->db
        ->join('categories', 'categories.id = products.category_id', 'left');

      if ($item_name) {
        $this->db
          ->group_start()
          ->like('products.code', $item_name, 'both')
          ->or_like('products.name', $item_name, 'both')
          ->group_end();
      }

      if ($category_id) {
        $this->db->where('products.category_id', $category_id);
      }

      $this->db
        ->where_in('products.type', ['standard'])
        ->where_not_in('products.category_id', [2, 14, 16, 17, 18]); // No Assets and Sub-Assets.

      $q = $this->db->get();

      $excel = $this->ridintek->spreadsheet();
      $excel->setTitle('Inventory Balance');

      if ($q->num_rows() > 0) {
        $excel->setCellValue('A1', 'Product Code');
        $excel->setCellValue('B1', 'Produt Name');
        $excel->setCellValue('C1', 'Unit');
        $excel->setCellValue('D1', 'Category');
        $excel->setCellValue('E1', 'Type');
        $excel->setCellValue('F1', 'Internal Use Type');
        $excel->setCellValue('G1', 'Beginning');
        $excel->setCellValue('H1', 'Increase');
        $excel->setCellValue('I1', 'Decrease');
        $excel->setCellValue('J1', 'Balance');
        $excel->setCellValue('K1', 'Average Cost');
        $excel->setCellValue('L1', 'Stock Value');

        $row = 2;

        foreach ($q->result() as $data_row) {
          $excel->setCellValue('A' . $row, $data_row->product_code);
          $excel->setCellValue('B' . $row, $data_row->product_name);
          $excel->setCellValue('C' . $row, $data_row->product_unit);
          $excel->setCellValue('D' . $row, $data_row->category_name);
          $excel->setCellValue('E' . $row, $data_row->product_type);
          $excel->setCellValue('F' . $row, $data_row->iuse_type);
          $excel->setCellValue('G' . $row, $data_row->beginning);
          $excel->setCellValue('H' . $row, $data_row->increase);
          $excel->setCellValue('I' . $row, $data_row->decrease);
          $excel->setCellValue('J' . $row, $data_row->balance);
          $excel->setCellValue('K' . $row, ceil(filterDecimal($data_row->new_cost)));
          $excel->setCellValue('L' . $row, ceil(filterDecimal($data_row->stock_value)));

          $row++;
        }
      }

      $name = $this->session->userdata('fullname');

      $excel->export('PrintERP-InventoryBalance-ByItem-' . date('Ymd_His') . "-($name)");
    } else if ($xls == 2) { //! Export Warehouse Summary Details.
      $data = [];
      $warehouses = $this->site->getWarehouses();

      $products = $this->site->getProducts(['type' => 'standard']); // Standard only.

      foreach ($warehouses as $warehouse) {
        if ($warehouse->code == 'ADV') continue;

        $totalCost = 0;

        foreach ($products as $product) {
          if ($product->category_id == 2) continue; // No asset(2).

          $incQty = 0;
          $decQty = 0;
          $itemCost = 0;

          $stocks = $this->site->getStocks(['product_id' => $product->id, 'warehouse_id' => $warehouse->id]);

          foreach ($stocks as $stock) {
            if ($stock->status == 'received') {
              $incQty += $stock->quantity;
            } else if ($stock->status == 'sent') {
              $decQty += $stock->quantity;
            }
          }

          $totalQty = $incQty - $decQty;

          if ($warehouse->code == 'LUC') {
            $itemCost  = $totalQty * $product->cost;
          } else {
            $itemCost  = $totalQty * $product->markon_price;
          }

          $totalCost += $itemCost;
        }

        $data[] = [
          'warehouse_name' => $warehouse->name,
          'cost' => round($totalCost)
        ];
      }

      if ($data) {
        $excel = $this->ridintek->spreadsheet();
        $excel->setTitle('Warehouse Summary Details');

        $excel->setCellValue('A1', 'Warehouses');
        $excel->setCellValue('B1', 'Total Amount');
        $excel->setBold('A1:B1');

        $row = 2;

        foreach ($data as $wh_data) {
          $excel->setCellValue('A' . $row, $wh_data['warehouse_name']);
          $excel->setCellValue('B' . $row, $wh_data['cost']);

          $row++;
        }

        $excel->setColumnAutoWidth('A');
        $excel->setColumnAutoWidth('B');

        $name = $this->session->userdata('fullname');

        $excel->export('Warehouse_Summary_Details_' . date('Ymd_His') . "-($name)");
      }
    }
  }

  public function getQMSReport()
  {
  }

  public function getUsabilityReport()
  {
    $startDate  = $this->input->get('start_date') ?? date('Y-m-') . '01';
    $endDate    = $this->input->get('end_date') ?? date('Y-m-d');

    $iuseItems = DB::table('stocks')->isNotNull('internal_use_id')
      ->where("created_at BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59'")->get();

    $sheet = $this->ridintek->spreadsheet();
    $sheet->loadFile(FCPATH . 'files/templates/Usability_Report.xlsx');

    $sheet->getSheetByName('Sheet1');
    $sheet->setTitle('Usability Report');
    // $sheet->setCellValue('A1', date('F Y', strtotime($startDate)));

    $r = 3;

    foreach ($iuseItems as $item) {
      $iuse = InternalUse::getRow(['id' => $item->internal_use_id]);

      // if ($iuse->category != 'sparepart') continue; // Kabeh category.

      $sameItems = DB::table('stocks')
        ->select('stocks.*')
        ->join('internal_uses', 'internal_uses.id = stocks.internal_use_id', 'left')
        ->isNotNull('stocks.internal_use_id')
        ->where('stocks.product_id', $item->product_id)
        ->where('internal_uses.to_warehouse_id', $iuse->to_warehouse_id)
        ->orderBy('stocks.internal_use_id', 'ASC')->get();

      $machine      = Product::getRow(['id' => $item->machine_id]);
      $supplier     = Supplier::getRow(['id' => $iuse->supplier_id]);
      $ts           = User::getRow(['id' => $iuse->ts_id]);
      $warehouseTo  = Warehouse::getRow(['id' => $iuse->to_warehouse_id]);

      $nextItem = NULL;
      $nextIUse = NULL;
      $nextTS   = NULL;

      if ($sameItems) {
        for ($a = 0; $a < count($sameItems); $a++) {
          // Compare by Unique Code Replacement (UCR)
          if (!empty($sameItems[$a]->ucr) && strcmp($sameItems[$a]->ucr, $item->unique_code) == 0) {
            $nextItem = $sameItems[$a];
            $nextIUse = InternalUse::getRow(['id' => $nextItem->internal_use_id]);
            $nextTS   = User::getRow(['id' => $nextIUse->ts_id]);

            break;
          } else if ($sameItems[$a]->id == $item->id) {
            if (isset($sameItems[$a + 1])) {
              $nextItem = $sameItems[$a + 1];
              $nextIUse = InternalUse::getRow(['id' => $nextItem->internal_use_id]);
              $nextTS   = User::getRow(['id' => $nextIUse->ts_id]);

              break;
            }
          }
        }
      }

      // Usability Day
      if ($nextIUse) {
        $replacementDate  = new DateTime($nextIUse->created_at);
        $installDate      = new DateTime($iuse->created_at);

        $usabilityDays = $installDate->diff($replacementDate)->format('%a'); // total days
      } else {
        $usabilityDays = 0;
      }

      // Usability Counter
      if ($nextItem) {
        $replacementCounter = intval($nextItem->spec);
        $installCounter     = intval($item->spec);

        $usabilityCounter = $replacementCounter - $installCounter;
      } else {
        $usabilityCounter = 0;
      }

      $sheet->setCellValue("A{$r}", $r - 2);
      $sheet->setCellValue("B{$r}", $iuse->created_at);
      $sheet->setCellValue("C{$r}", $warehouseTo->name);
      $sheet->setCellValue("D{$r}", $item->product_name);
      $sheet->setCellValue("E{$r}", $item->cost);
      $sheet->setCellValue("F{$r}", $item->price);
      $sheet->setCellValue("G{$r}", ($supplier ? $supplier->name : ''));
      $sheet->setCellValue("H{$r}", ''); // Order date
      $sheet->setCellValue("I{$r}", $item->unique_code); // Unique Code
      $sheet->setCellValue("J{$r}", ($machine ? $machine->name . " ({$machine->warehouses})" : '')); // Machine name and Warehouse

      // Installation
      $sheet->setCellValue("K{$r}", $iuse->created_at); // Installation reference.
      $sheet->setCellValue("L{$r}", $iuse->reference); // Installation date.
      $sheet->setCellValue("M{$r}", $item->spec); // Installation counter.
      $sheet->setCellValue("N{$r}", ($ts ? $ts->fullname : '')); // Installation TS.

      // Replacement
      $sheet->setCellValue("O{$r}", ($nextIUse ? $nextIUse->created_at : '')); // Installation reference.
      $sheet->setCellValue("P{$r}", ($nextIUse ? $nextIUse->reference : '')); // Replacement date.
      $sheet->setCellValue("Q{$r}", ($nextItem ? $nextItem->spec : '')); // Replacement counter.
      $sheet->setCellValue("R{$r}", ($nextTS ? $nextTS->fullname : '')); // Replacement TS.

      $sheet->setCellValue("S{$r}", $usabilityDays); // Usability (day).
      $sheet->setCellValue("T{$r}", $usabilityCounter); // Usability (counter).
      $sheet->setCellValue("U{$r}", htmlRemove($iuse->note)); // Note.

      $r++;
    }

    $name = $this->session->userdata('fullname');

    $sheet->export('PrintERP-SparepartUsabilityReport-' . date('Ymd_His') . "-($name)");
  }

  public function getWarehouseStockAlerts($warehouse_id = null, $pdf = null, $xls = null)
  { // Added Custom
    $this->sma->checkPermissions('quantity_alerts', true);
    if (!$this->Owner && !$warehouse_id) {
      $user         = $this->site->getUser($this->session->userdata('user_id'));
      $warehouse_id = $user->warehouse_id;
    }

    $clauses = '';

    $wh_from_id = $this->site->getWarehouseByName('Lucretia')->id;

    if ($warehouse_id) {
      $clauses .= " AND warehouse_id = {$warehouse_id}";
    }

    if (!$pdf && !$xls) { // WEB
      $this->load->library('datatables');
      $this->datatables
        ->select("products.id as id, products.image as product_image,
          products.code as product_code, products.name as product_name,
          (COALESCE(stock_recv.total_qty, 0) - COALESCE(stock_sent.total_qty, 0)) AS current_stock,
          (COALESCE(from_stock_recv.total_qty, 0) - COALESCE(from_stock_sent.total_qty, 0)) AS current_from_stock,
          warehouses.name as wh_name,
          warehouses_products.safety_stock as whp_safe_stock, warehouses.id as wh_id")
        ->from('warehouses_products')
        ->join('products', 'products.id = warehouses_products.product_id', 'left')
        ->join('warehouses', 'warehouses.id = warehouses_products.warehouse_id', 'left')
        ->join(
          "(
          SELECT
            product_id,
            SUM(quantity) AS total_qty FROM stocks
          WHERE status LIKE 'received' {$clauses} GROUP BY product_id) stock_recv",
          'stock_recv.product_id = warehouses_products.product_id',
          'left'
        )
        ->join(
          "(
          SELECT
            product_id,
            SUM(quantity) AS total_qty FROM stocks
          WHERE status LIKE 'sent' {$clauses} GROUP BY product_id) stock_sent",
          'stock_sent.product_id = warehouses_products.product_id',
          'left'
        )
        ->join(
          "(
          SELECT
            product_id,
            SUM(quantity) AS total_qty FROM stocks
          WHERE status LIKE 'received' AND warehouse_id = {$wh_from_id} GROUP BY product_id) from_stock_recv",
          'from_stock_recv.product_id = warehouses_products.product_id',
          'left'
        )
        ->join(
          "(
          SELECT
            product_id,
            SUM(quantity) AS total_qty FROM stocks
          WHERE status LIKE 'sent' AND warehouse_id = {$wh_from_id} GROUP BY product_id) from_stock_sent",
          'from_stock_sent.product_id = warehouses_products.product_id',
          'left'
        )
        ->where('warehouses_products.safety_stock > (stock_recv.total_qty - stock_sent.total_qty)')
        ->where('warehouses_products.safety_stock <> 0')
        ->where('warehouses_products.safety_stock IS NOT NULL');

      if ($warehouse_id) {
        $this->datatables->where('warehouses.id', $warehouse_id);
      }

      echo $this->datatables->generate();
    } else if ($pdf || $xls) { // EXCEL
      if ($warehouse_id) {
        $this->db
          ->select("products.id as id, products.image as product_image,
          products.code as product_code, products.name as product_name,
          warehouses_products.quantity as whp_qty, wh_from.quantity as src_qty, warehouses.name as wh_name,
          warehouses_products.safety_stock as whp_safe_stock")
          ->from('warehouses_products')
          ->join('products', 'warehouses_products.product_id = products.id', 'left')
          ->join('warehouses', 'warehouses_products.warehouse_id = warehouses.id', 'left')
          ->join("(SELECT id, product_id, warehouse_id, quantity, safety_stock FROM warehouses_products
            WHERE warehouse_id = {$wh_from_id} GROUP BY id) wh_from", 'wh_from.product_id=warehouses_products.product_id', 'left')
          ->where('warehouses_products.quantity < warehouses_products.safety_stock')
          ->where('warehouses_products.safety_stock <> 0')
          ->where('warehouses.id', $warehouse_id);
      } else {
        $this->db
          ->select("products.id as id, products.image as product_image,
          products.code as product_code, products.name as product_name,
          warehouses_products.quantity as whp_qty, wh_from.quantity as src_qty, warehouses.name as wh_name,
          warehouses_products.safety_stock as whp_safe_stock")
          ->from('warehouses_products')
          ->join('products', 'warehouses_products.product_id = products.id', 'left')
          ->join('warehouses', 'warehouses_products.warehouse_id = warehouses.id', 'left')
          ->join("(SELECT id, product_id, warehouse_id, quantity, safety_stock FROM warehouses_products
            WHERE warehouse_id = {$wh_from_id} GROUP BY id) wh_from", 'wh_from.product_id=warehouses_products.product_id', 'left')
          ->where('warehouses_products.quantity < warehouses_products.safety_stock')
          ->where('warehouses_products.safety_stock <> 0');
      }

      $q = $this->db->get();
      if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
          $data[] = $row;
        }
      } else {
        $data = null;
      }

      if (!empty($data)) { // Passed.
        $excel = $this->ridintek->spreadsheet();
        $excel->setTitle(lang('warehouse_safety_alert'));
        $excel->SetCellValue('A1', lang('no'));
        $excel->SetCellValue('B1', lang('product_code'));
        $excel->SetCellValue('C1', lang('product_name'));
        $excel->SetCellValue('D1', 'Stock');
        $excel->SetCellValue('E1', 'Lucretia Stock');
        $excel->SetCellValue('F1', lang('warehouse'));
        $excel->SetCellValue('G1', lang('safety_stock'));

        $row = 2;
        foreach ($data as $data_row) {
          $excel->SetCellValue('A' . $row, strval($row - 1));
          $excel->SetCellValue('B' . $row, $data_row->product_code);
          $excel->SetCellValue('C' . $row, $data_row->product_name);
          $excel->SetCellValue('D' . $row, $data_row->whp_qty);
          $excel->SetCellValue('E' . $row, $data_row->src_qty);
          $excel->SetCellValue('F' . $row, $data_row->wh_name);
          $excel->SetCellValue('G' . $row, $data_row->whp_safe_stock);
          $row++;
        }

        $excel->setColumnAutoWidth('A');
        $excel->setColumnAutoWidth('B');
        $excel->setColumnAutoWidth('C');
        $excel->setColumnAutoWidth('D');
        $excel->setColumnAutoWidth('E');
        $excel->setColumnAutoWidth('F');
        $excel->setColumnAutoWidth('G');

        $filename = 'warehouse_stock_alert';
        $excel->export($filename);
      }
      $this->session->set_flashdata('error', lang('nothing_found'));
      redirect($_SERVER['HTTP_REFERER']);
    }
  }

  public function getSalesStatus($xls = null)
  { // Added, custom of [getSalesReport => (ignored)].
    $this->sma->checkPermissions('sales', true);
    $group_by     = $this->input->get('group_by') ?? 'sale'; // sale as default
    $product      = $this->input->get('product');
    $categories   = $this->input->get('categories') ?? [];
    $users        = $this->input->get('users') ?? [];
    $customer     = $this->input->get('customer');
    $biller       = $this->input->get('biller');
    $warehouse    = $this->input->get('warehouse');
    $reference    = $this->input->get('reference');
    $start_date   = $this->input->get('start_date');
    $end_date     = $this->input->get('end_date');

    $warehouse = ($this->session->userdata('warehouse_id') ?? $warehouse);

    if ($start_date) {
      $start_date = $start_date . ' 00:00:00';
      $end_date   = $end_date . ' 23:59:59';
    }
    if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
      $users[] = $this->session->userdata('user_id');
    }

    if (!$xls) { // WEB
      /* QUERIES */
      if ($group_by == 'biller') {
        $query = "'-' as date, '-' as reference, '-' as pic_id, '-' as pic_name, billers.name as biller_name,
          '-' as customer_name, '-' as product_code, '-' as product_name, '-' as category_code,
          SUM(sales.total_items) as total_items,";
      }
      if ($group_by == 'customer') {
        $query = "sales.date as date, sales.reference as reference,
          users.username as pic_id,
          users.fullname as pic_name,
          billers.name as biller_name,
          CONCAT(customers.company, ' (', customers.name, ')') as customer_name,
          '-' as product_code, '-' as product_name, '-' as category_code,
          SUM(sales.total_items) as total_items,";
      }
      if ($group_by == 'pic') {
        $query = "'-' as date, '-' as reference,
          users.username as pic_id,
          users.fullname as pic_name,
          billers.name as biller_name, '-' as customer_name,
          '-' as product_code, '-' as product_name, '-' as category_code,
          SUM(sales.total_items) as total_items,";
      }
      if ($group_by == 'product') {
        $query = "'-' as date, '-' as reference, '-' as pic_id, '-' as pic_name, '-' as biller_name,
          '-' as customer_name,
          products.code as product_code, products.name as product_name,
          categories.code as category_code,
          SUM(sales.total_items) as total_items,";
      }
      if ($group_by == 'category') {
        $query = "'-' as date, '-' as reference, '-' as pic_id, '-' as pic_name, '-' as biller_name,
          '-' as customer_name,
          products.code as product_code, products.name as product_name,
          categories.code as category_code,
          SUM(sales.total_items) as total_items,";
      }
      if ($group_by == 'sale') { // Default
        $query = "sales.date as date, sales.reference as reference,
          users.username as pic_id,
          users.fullname as pic_name,
          billers.name as biller_name,
          CONCAT(customers.company, ' (', customers.name, ')') as customer_name,
          '-' as product_code, '-' as product_name,
          '-' as category_code, sales.total_items as total_items,";
      }

      if ($group_by != 'category' && $group_by != 'product') { // Anything except category or product.
        if ($group_by == 'biller' || $group_by == 'customer' || $group_by == 'sale') {
          // $query .= "SUM(sales.grand_total) as grand_total,
          //   SUM(sales.paid) as paid,
          //   (CASE WHEN
          //     (customers.customer_group_name NOT LIKE 'Reguler' OR sales.paid > 0)
          //   THEN SUM(sales.grand_total) - SUM(sales.paid)
          //   ELSE 0 END) as balance,";
          $query .= "SUM(sales.grand_total) as grand_total,
            SUM(sales.paid) as paid,
            SUM(sales.balance) as balance,";
        } else {
          $query .= "SUM(sales.grand_total) as grand_total, SUM(sales.paid) as paid,
            SUM(sales.grand_total) - SUM(sales.paid) as balance,";
        }
      } else if ($group_by == 'category' || $group_by == 'product') { // Category and Product
        $query .= "SUM(sale_item.subtotal) as grand_total, '0' as paid, '0' as balance,";
      }

      if ($group_by == 'sale') {
        $query .= "'-' AS operator_name, sales.status AS status,";
      }
      if ($group_by != 'product') {
        $query .= "'-' AS operator_name, '-' AS status,";
      } else {
        $query .= "operator.fullname AS operator_name,
          sales.status AS status,";
      }

      if ($group_by != 'sale' && $group_by != 'customer') {
        $query .= "'-' as payment_status,";
      } else {
        $query .= "sales.payment_status as payment_status,";
      }

      $query .= "sales.id as id";

      /* EXECUTE QUERIES */
      $this->load->library('datatables');
      $this->datatables->select($query)->from('sales');

      /* JOIN TABLES */
      if ($group_by == 'product' || $group_by == 'category') {
        $this->datatables->join('sale_items AS sale_item', "sale_item.sale_id = sales.id", 'left');
      }
      if ($group_by == 'product') {
        $this->datatables->join('users AS operator', "operator.id = JSON_UNQUOTE(JSON_EXTRACT(sale_item.json_data, '$.operator_id'))", 'left');
      }
      if ($group_by == 'biller' || $group_by == 'customer' || $group_by == 'pic' || $group_by == 'sale') {
        $this->datatables->join('billers', 'billers.id=sales.biller_id', 'left');
      }
      if ($group_by == 'biller' || $group_by == 'customer' || $group_by == 'sale') {
        $this->datatables->join('customers', 'customers.id=sales.customer_id', 'left');
      }
      if ($group_by == 'customer' || $group_by == 'pic' || $group_by == 'sale') {
        $this->datatables->join('users', 'users.id=sales.created_by', 'left');
      }
      if ($group_by == 'category' || $group_by == 'product') {
        $this->datatables->join('products', 'products.id=sale_item.product_id', 'left');
        $this->datatables->join('categories', 'categories.id=products.category_id', 'left');
      }

      /* GROUPS */
      switch ($group_by) {
        case 'biller':
          $this->datatables->group_by('sales.biller_id');
          break;
        case 'customer':
          $this->datatables->group_by('customers.id');
          break;
        case 'pic':
          $this->datatables->group_by('sales.created_by');
          break;
        case 'product':
          $this->datatables->group_by('sale_item.product_id');
          break;
        case 'category':
          $this->datatables->group_by('categories.id');
          break;
        case 'sale':
          $this->datatables->group_by('sales.id');
          break;
      }

      /* FILTERS */
      if ($users) {
        foreach ($users as $user) {
          $this->datatables->or_where('sales.created_by', $user);
        }
      }
      if ($product && $group_by == 'product') {
        $this->datatables->where('sale_item.product_id', $product);
      }
      if ($categories && ($group_by == 'category' || $group_by == 'product')) {
        foreach ($categories as $product_category) {
          $this->datatables->or_where('categories.id', $product_category);
        }
      }
      if ($biller) {
        $this->datatables->where('sales.biller_id', $biller);
      }
      if ($customer) {
        $this->datatables->where('sales.customer_id', $customer);
      }
      if ($warehouse) {
        $this->datatables->where('sales.warehouse_id', $warehouse);
      }
      if ($reference) {
        $this->datatables->like('sales.reference', $reference, 'both');
      }
      if ($start_date) {
        $this->datatables->where('sales.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
      }
      $this->datatables->where("sales.status NOT LIKE 'need_payment'"); // need_payment = not debt.
      $this->datatables->where("sales.status NOT LIKE 'draft'"); // No draft.

      // GENERATE VIEW
      echo $this->datatables->generate();
    } else if ($xls) { // Export Excel.
      /* QUERIES */
      if ($group_by == 'biller') {
        $query = "'-' as date, '-' as reference, '-' as pic_id, '-' as pic_name, billers.name as biller_name,
          '-' as customer_name, '-' as customer_phone,
          '-' as product_code, '-' as product_name, '-' as category_code,
          SUM(sales.total_items) as total_items,";
      }
      if ($group_by == 'customer') {
        $query = "sales.date as date, sales.reference as reference,
          users.username as pic_id,
          users.fullname as pic_name,
          billers.name as biller_name,
          CONCAT(customers.company, ' (', customers.name, ')') as customer_name,
          customers.phone AS customer_phone,
          '-' as product_code, '-' as product_name, '-' as category_code,
          SUM(sales.total_items) as total_items,";
      }
      if ($group_by == 'pic') {
        $query = "'-' as date, '-' as reference,
          users.username as pic_id,
          users.fullname as pic_name,
          billers.name as biller_name, '-' as customer_name, '-' as customer_phone,
          '-' as product_code, '-' as product_name, '-' as category_code,
          SUM(sales.total_items) as total_items,";
      }
      if ($group_by == 'product') {
        $query = "'-' as date, '-' as reference, '-' as pic_id, '-' as pic_name, '-' as biller_name,
          '-' as customer_name, '-' as customer_phone,
          products.code as product_code, products.name as product_name,
          categories.code as category_code,
          SUM(sales.total_items) as total_items,";
      }
      if ($group_by == 'category') {
        $query = "'-' as date, '-' as reference, '-' pic_id, '-' as pic_name, '-' as biller_name,
          '-' as customer_name, '-' as customer_phone,
          products.code as product_code, products.name as product_name,
          categories.code as category_code,
          SUM(sales.total_items) as total_items,";
      }
      if ($group_by == 'sale') { // Default
        $query = "sales.date as date, sales.reference as reference,
          users.username as pic_id,
          users.fullname as pic_name,
          billers.name as biller_name,
          CONCAT(customers.company,' (', customers.name, ')') as customer_name,
          customers.phone AS customer_phone,
          '-' as product_code, '-' as product_name,
          '-' as category_code, SUM(sales.total_items) as total_items,";
      }

      if ($group_by != 'category' && $group_by != 'product') { // Anything except category or product.
        if ($group_by == 'biller' || $group_by == 'customer' || $group_by == 'sale') {
          // $query .= "SUM(sales.grand_total) as grand_total,
          //   SUM(sales.paid) as paid,
          //   (CASE WHEN
          //     (customers.customer_group_name NOT LIKE 'Reguler' OR sales.paid > 0)
          //   THEN SUM(sales.grand_total) - SUM(sales.paid)
          //   ELSE 0 END) as balance,";
          $query .= "SUM(sales.grand_total) as grand_total,
            SUM(sales.paid) as paid,
            SUM(sales.balance) as balance,";
        } else {
          $query .= "SUM(sales.grand_total) as grand_total, SUM(sales.paid) as paid,
            SUM(sales.grand_total) - SUM(sales.paid) as balance,";
        }
      } else {
        $query .= "SUM(sale_item.subtotal) as grand_total, '0' as paid, '0' as balance,";
      }

      if ($group_by != 'sale' && $group_by != 'product') {
        $query .= "'-' AS operator_name, '-' AS completed_date, '-' AS status,";
      } else {
        $query .= "operator.fullname AS operator_name,
          sale_item.json_data->>'$.updated_at' AS completed_date,
          sales.status AS status,";
      }

      if ($group_by != 'sale' && $group_by != 'customer') {
        $query .= "'-' as payment_status,";
      } else {
        $query .= "sales.payment_status as payment_status,";
      }

      $query .= "sales.id as id";

      /* EXECUTE QUERIES */
      $this->db->select($query, FALSE)->from('sales');

      /* JOIN TABLES */
      if ($group_by == 'sale' || $group_by == 'product' || $group_by == 'category') {
        $this->db->join('sale_items AS sale_item', "sale_item.sale_id = sales.id", 'left');
      }
      if ($group_by == 'sale' || $group_by == 'product') {
        $this->db->join('users AS operator', "operator.id = JSON_UNQUOTE(JSON_EXTRACT(sale_item.json_data, '$.operator_id'))", 'left');
      }
      if ($group_by == 'biller' || $group_by == 'customer' || $group_by == 'pic' || $group_by == 'sale') {
        $this->db->join('billers', 'billers.id=sales.biller_id', 'left');
      }
      if ($group_by == 'biller' || $group_by == 'customer' || $group_by == 'sale') {
        $this->db->join('customers', 'customers.id=sales.customer_id', 'left');
      }
      if ($group_by == 'customer' || $group_by == 'pic' || $group_by == 'sale') {
        $this->db->join('users', 'users.id=sales.created_by', 'left');
      }
      if ($group_by == 'category' || $group_by == 'product') {
        $this->db->join('products', 'products.id=sale_item.product_id', 'left');
        $this->db->join('categories', 'categories.id=products.category_id', 'left');
      }

      /* GROUPS */
      switch ($group_by) {
        case 'biller':
          $this->db->group_by('sales.biller_id');
          break;
        case 'customer':
          $this->db->group_by('customers.id');
          break;
        case 'pic':
          $this->db->group_by('sales.created_by');
          break;
        case 'product':
          $this->db->group_by('sale_item.product_id');
          break;
        case 'category':
          $this->db->group_by('categories.id');
          break;
        case 'sale':
          $this->db->group_by('sales.id');
          break;
      }

      /* FILTERS */
      if ($users) {
        foreach ($users as $user) {
          $this->db->or_where('sales.created_by', $user);
        }
      }
      if ($product && $group_by == 'product') {
        $this->db->where('sale_item.product_id', $product);
      }
      if ($categories && $group_by == 'category') {
        foreach ($categories as $product_category) {
          $this->db->or_where('categories.id', $product_category);
        }
      }
      if ($biller) {
        $this->db->where('sales.biller_id', $biller);
      }
      if ($customer) {
        $this->db->where('sales.customer_id', $customer);
      }
      if ($warehouse) {
        $this->db->where('sales.warehouse_id', $warehouse);
      }
      if ($reference) {
        $this->db->like('sales.reference', $reference, 'both');
      }
      if ($start_date) {
        $this->db->where('sales.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
      }
      $this->db->where("sales.status NOT LIKE 'need_payment'"); // status == need_payment == not debt.
      $this->db->where("sales.status NOT LIKE 'draft'"); // No draft.

      $q = $this->db->get();

      if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
          $data[] = $row;
        }
      } else {
        $data = null;
      }

      if (!empty($data)) {
        $excel = $this->ridintek->spreadsheet();
        $excel->setTitle('Sales Status');
        $excel->SetCellValue('A1', lang('date'));
        $excel->SetCellValue('B1', lang('reference'));
        $excel->SetCellValue('C1', lang('pic_id'));
        $excel->SetCellValue('D1', lang('pic_name'));
        $excel->SetCellValue('E1', lang('biller'));
        $excel->SetCellValue('F1', lang('phone'));
        $excel->SetCellValue('G1', lang('customer'));
        $excel->SetCellValue('H1', lang('product_code'));
        $excel->SetCellValue('I1', lang('product_name'));
        $excel->SetCellValue('J1', lang('product_category'));
        $excel->SetCellValue('K1', lang('quantity'));
        $excel->SetCellValue('L1', lang('grand_total'));
        $excel->SetCellValue('M1', lang('paid'));
        $excel->SetCellValue('N1', lang('balance'));
        $excel->SetCellValue('O1', lang('operator'));
        $excel->SetCellValue('P1', lang('completed_date'));
        $excel->SetCellValue('Q1', lang('status'));
        $excel->SetCellValue('R1', lang('payment_status'));

        $excel->setBold('A1:R1', TRUE);
        $excel->setFillColor('A1:R1', 'FFFF00');

        $row     = 2;
        $total   = 0;
        $paid    = 0;
        $balance = 0;

        foreach ($data as $data_row) {
          $excel->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
          $excel->SetCellValue('B' . $row, $data_row->reference);
          $excel->SetCellValue('C' . $row, $data_row->pic_id);
          $excel->SetCellValue('D' . $row, $data_row->pic_name);
          $excel->SetCellValue('E' . $row, $data_row->biller_name);
          $excel->SetCellValue('F' . $row, $data_row->customer_phone ?? '', DataType::TYPE_STRING);
          $excel->SetCellValue('G' . $row, $data_row->customer_name ?? '');
          $excel->SetCellValue('H' . $row, $data_row->product_code);
          $excel->SetCellValue('I' . $row, $data_row->product_name);
          $excel->SetCellValue('J' . $row, $data_row->category_code);
          $excel->SetCellValue('K' . $row, $data_row->total_items);
          $excel->SetCellValue('L' . $row, $data_row->grand_total);
          $excel->SetCellValue('M' . $row, $data_row->paid);
          $excel->SetCellValue('N' . $row, ($group_by != 'category' && $group_by != 'product' ? ($data_row->grand_total - $data_row->paid) : 0));
          $excel->SetCellValue('O' . $row, $data_row->operator_name);
          $excel->SetCellValue('P' . $row, $data_row->completed_date);
          $excel->SetCellValue('Q' . $row, lang($data_row->status));
          $excel->SetCellValue('R' . $row, lang($data_row->payment_status));
          $total   += $data_row->grand_total;
          $paid    += $data_row->paid;
          $balance += ($data_row->grand_total - $data_row->paid);
          $row++;
        }

        // Begin Set Fill Color by Group
        if ($group_by == 'biller') $excel->setFillColor('E2:E' . ($row - 1), 'C0FFC0');
        if ($group_by == 'customer') {
          $excel->setFillColor('F2:F' . ($row - 1), 'C0FFC0');
          $excel->setFillColor('G2:G' . ($row - 1), 'C0FFC0');
        }
        if ($group_by == 'pic') {
          $excel->setFillColor('C2:C' . ($row - 1), 'C0FFC0');
          $excel->setFillColor('D2:D' . ($row - 1), 'C0FFC0');
        }
        if ($group_by == 'product') {
          $excel->setFillColor('H2:H' . ($row - 1), 'C0FFC0');
          $excel->setFillColor('I2:I' . ($row - 1), 'C0FFC0');
        }
        if ($group_by == 'category') $excel->setFillColor('J2:J' . ($row - 1), 'C0FFC0');
        if ($group_by == 'sale') $excel->setFillColor('B2:B' . ($row - 1), 'C0FFC0');
        // End Set Fill Color by Group

        if ($group_by == 'category' || $group_by == 'product') $balance = 0;

        $excel->SetCellValue('L' . $row, $total);
        $excel->SetCellValue('M' . $row, $paid);
        $excel->SetCellValue('N' . $row, $balance);

        $excel->setColumnAutoWidth('A');
        $excel->setColumnAutoWidth('B');
        $excel->setColumnAutoWidth('C');
        $excel->setColumnAutoWidth('D');
        $excel->setColumnAutoWidth('E');
        $excel->setColumnAutoWidth('F');
        $excel->setColumnAutoWidth('G');
        $excel->setColumnAutoWidth('H');
        $excel->setColumnAutoWidth('I');
        $excel->setColumnAutoWidth('J');
        $excel->setColumnAutoWidth('K');
        $excel->setColumnAutoWidth('L');
        $excel->setColumnAutoWidth('M');
        $excel->setColumnAutoWidth('N');
        $excel->setColumnAutoWidth('O');
        $excel->setColumnAutoWidth('P');
        $excel->setColumnAutoWidth('Q');
        $excel->setColumnAutoWidth('R');

        $name = $this->session->userdata('fullname');
        $file_group_by = ($group_by ? '-' . lang($group_by) : '');

        $filename = 'PrintERP-Sales_Status-' . date('Ymd_His') . $file_group_by . "-($name)";
        $excel->export($filename);
      }
      $this->session->set_flashdata('error', lang('nothing_found'));
      redirect($_SERVER['HTTP_REFERER']);
    }
  }

  public function getSoldItems()
  {
    $billerId     = $this->input->get('biller');
    $warehouseId  = $this->input->get('warehouse');
    $startDate    = $this->input->get('start_date');
    $endDate      = $this->input->get('end_date');

    $clause = [
      'not_null' => 'sale_id'
    ];

    // Biller not filtered here, but on LOOP.
    // if ($warehouseId) $clause['warehouse_id'] = $warehouseId;
    if ($warehouseId) $clause['warehouse_id'] = $warehouseId;
    if ($startDate)   $clause['start_date']   = $startDate;
    if ($endDate)     $clause['end_date']     = $endDate;

    $stocks = Stock::get($clause);

    // dbgprint($clause);die;
    // dbgprint($stocks); die;

    $sheet = $this->ridintek->spreadsheet();

    $sheet->setTitle('RAW Material');

    $sheet->setCellValue('A1', 'Invoice Sale');
    $sheet->setCellValue('B1', 'Product Code');
    $sheet->setCellValue('C1', 'Product Name');
    $sheet->setCellValue('D1', 'Markon Price');
    $sheet->setCellValue('E1', 'Quantity');
    $sheet->setCellValue('F1', 'Total');

    $r = 2;

    foreach ($stocks as $stock) {
      $sale = Sale::getRow(['id' => $stock->sale_id]);

      if ($billerId) { // Biller filtered here.
        if ($sale->biller_id != $billerId) continue;
      }

      $sheet->setCellValue('A' . $r, $sale->reference);
      $sheet->setCellValue('B' . $r, $stock->product_code);
      $sheet->setCellValue('C' . $r, $stock->product_name);
      $sheet->setCellValue('D' . $r, $stock->price);
      $sheet->setCellValue('E' . $r, $stock->quantity);
      $sheet->setCellValue('F' . $r, ($stock->price * $stock->quantity));

      $r++;
    }

    $date = date('Ymd_His');
    $user = $this->session->userdata('fullname');

    if ($stocks) {
      $lr = ($r - 1);
      $r++;
      $sheet->setCellValue('E' . $r, 'GRAND TOTAL');
      $sheet->setCellValue('F' . $r, "=SUM(F2:F{$lr})");
    }

    $sheet->setColumnAutoWidth('A');
    $sheet->setColumnAutoWidth('B');
    $sheet->setColumnAutoWidth('C');
    $sheet->setColumnAutoWidth('D');
    $sheet->setColumnAutoWidth('E');
    $sheet->setColumnAutoWidth('F');

    $sheet->export("PrintERP-RAWMaterial-{$date}-{$user}");
  }

  public function index()
  {
    admin_redirect('reports/printerp');
  }

  public function daily_performance()
  {
    // $this->sma->checkUserPermissions('reports-daily_performance');
    checkPermission('reports-daily_performance');

    $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $this->data['categories'] = $this->site->getParentCategories();
    $this->data['warehouses'] = Warehouse::get(['active' => '1']);

    $this->data['period']       = $this->input->get('period');
    $this->data['xls']          = $this->input->get('xls');

    if (empty($this->data['period'])) {
      $period = new DateTime();
      $this->data['period'] = $period->format('Y-m');
    } else {
      $period = new DateTime($this->data['period'] . '-01');
    }

    $this->data['start_date']   = $this->data['period'] . '-01';
    $this->data['end_date']     = $this->data['period'] . '-' . $period->format('t');

    $this->data = getCurrentMonthPeriod($this->data);

    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('reports')],
      ['link' => '#', 'page' => lang('daily_performance')]
    ];
    $meta = ['page_title' => lang('daily_performance'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('reports/daily_performance', $this->data);
  }

  public function income_statement()
  {
    $this->sma->checkUserPermissions('reports-income_statement');
    // checkPermission('reports-income_statement');

    $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $this->data['categories'] = $this->site->getParentCategories();
    $this->data['warehouses'] = $this->site->getAllWarehouses();

    $warehouse_id = $this->input->get('warehouse');
    $this->data['category_id']  = $this->input->get('category');
    $this->data['item_name']    = $this->input->get('item_name');
    $this->data['start_date']   = $this->input->get('start_date');
    $this->data['end_date']     = $this->input->get('end_date');
    $this->data['warehouse_id'] = $this->input->get('warehouse');
    $this->data['xls']          = $this->input->get('xls');

    if ($warehouse_id) {
      $this->data['warehouse']    = $this->site->getWarehouseByID($warehouse_id);
    } else {
      $this->data['warehouse']    = NULL;
    }

    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('reports')],
      ['link' => '#', 'page' => lang('income_statement')]
    ];
    $meta = ['page_title' => lang('income_statement'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('reports/income_statement', $this->data);
  }

  public function inventory_balance()
  {
    $this->sma->checkUserPermissions('reports-inventory_balance');

    $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $this->data['categories'] = $this->site->getParentCategories();
    $this->data['warehouses'] = $this->site->getAllWarehouses();

    $warehouse_id = $this->input->get('warehouse');
    $this->data['category_id']  = $this->input->get('category');
    $this->data['item_name']    = $this->input->get('item_name');
    $this->data['start_date']   = $this->input->get('start_date');
    $this->data['end_date']     = $this->input->get('end_date');
    $this->data['warehouse_id'] = $this->input->get('warehouse');
    $this->data['xls']          = $this->input->get('xls');

    if ($warehouse_id) {
      $this->data['warehouse']    = $this->site->getWarehouseByID($warehouse_id);
    } else {
      $this->data['warehouse']    = NULL;
    }

    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('reports')],
      ['link' => '#', 'page' => lang('inventory_balance')]
    ];
    $meta = ['page_title' => lang('inventory_balance'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('reports/inventory_balance', $this->data);
  }

  public function payments()
  {
    $this->sma->checkPermissions('payments');
    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('reports'), 'page' => lang('reports')], ['link' => '#', 'page' => lang('payments_report')]];
    $meta = ['page_title' => lang('payments_report'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('reports/payments', $this->data);
  }

  public function printerp()
  {
    $meta = [
      'page_title' => 'PrintERP Reports',
      'bc' => [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => admin_url('reports'), 'page' => lang('reports')],
        ['link' => '#', 'page' => 'PrintERP Reports']
      ]
    ];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('reports/printerp', $this->data);
  }

  public function qms()
  {
    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('reports'), 'page' => lang('reports')],
      ['link' => '#', 'page' => 'QMS Report']
    ];
    $meta = ['page_title' => 'QMS Report', 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('reports/qms', $this->data);
  }

  /**
   * Report Machine Performance. (NEW)
   */
  public function machines()
  {
    $startDate = ($this->input->get('start_date') ?? date('Y-m-') . '01');
    $endDate   = ($this->input->get('end_date') ?? date('Y-m-d'));
    $whIds = $this->input->get('warehouse');

    $whNames = [];

    if ($whIds) {
      foreach ($whIds as $whId) {
        $warehouse = $this->site->getWarehouseByID($whId);

        if ($warehouse) {
          $whNames[] = $warehouse->name;
        }
      }
    }

    $this->db
      ->select("products.id AS product_id, products.code AS product_code, products.name AS product_name,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.sn')) AS sn,
          categories.name AS category_name,
          subcategories.name AS subcategory_name,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.assigned_at')) AS assigned_at,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.priority')) AS priority,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.order_date')) AS order_date,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.order_price')) AS order_price,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.maintenance_qty')) AS maintenance_qty,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.maintenance_cost')) AS maintenance_cost,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.disposal_date')) AS disposal_date,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.disposal_price')) AS disposal_price,
          products.active AS active,
          products.warehouses AS warehouses,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.condition')) AS last_condition,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.note')) AS note,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.pic_note')) AS pic_note,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.updated_at')) AS last_update,
          JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.purchased_at')) AS purchased_at,
          pic.fullname AS pic_name,,
          creator.fullname AS creator_name")
      ->from('products')
      ->join('categories', 'categories.id = products.category_id', 'left')
      ->join('categories AS subcategories', 'subcategories.id = products.subcategory_id', 'left')
      ->join('users AS creator', "creator.id = JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.updated_by'))", 'left')
      ->join('users AS pic', "pic.id = JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.pic_id'))", 'left')
      ->group_start()
      ->like('categories.code', 'AST', 'none')
      ->or_like('categories.code', 'EQUIP', 'none')
      ->group_end();

    if ($whNames) {
      $this->db->group_start();
      foreach ($whNames as $name) {
        $this->db->or_like('products.warehouses', $name, 'none');
      }
      $this->db->group_end();
    }

    $q = $this->db->get();

    // print_r($q->result()); die;

    if ($q) {
      $rows = $q->result();
    } else {
      die($this->db->error()['message']);
    }

    // Summary Report (TAKETOOLONGTIME)
    $A1DateGrid = [
      NULL, 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V',
      'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL'
    ];

    $sheet = $this->ridintek->spreadsheet();
    $sheet->loadFile(FCPATH . 'files/templates/Machine_Report.xlsx');

    $sheet->getSheetByName('Sheet1');
    $sheet->setTitle('Summary Report');
    $sheet->setCellValue('A1', date('F Y', strtotime($startDate)));

    $warehouses = $this->site->getWarehouses();
    $pg = 10000; // Penalty

    $r = 4;
    $lastDate = intval(date('j', strtotime($endDate)));

    goto skip_0;

    skip_0:

    foreach ($warehouses as $wh) {
      if ($wh->active == 0) continue;
      if ($wh->code == 'ADV') continue; // No ADV warehouse please.
      // if ($wh->code == 'IDSLOS') continue; // No IDS.
      // if ($wh->code == 'IDSUNG') continue; // No IDS.

      $sheet->setCellValue('A' . $r, $wh->name);
      $sheet->setCellValue('C' . $r, "=COUNTIF(H{$r}:AL{$r},\"X\")");
      $sheet->setCellValue('D' . $r, "=COUNTIF(H{$r}:AL{$r},\"P\")");
      $sheet->setCellValue('E' . $r, "=C{$r}+D{$r}");
      $sheet->setCellValue('F' . $r, "=IF(E{$r}>0,E{$r}*-{$pg},(\$E\$1*{$pg})/(LEFT(\$B\$2,SEARCH(\":\",\$B\$2)-1)))");

      for ($x = 1; $x <= $lastDate; $x++) {
        $items = [];
        $dayCode = date('D', strtotime(date('Y-m-', strtotime($endDate)) . $x));

        // No checked for other except DUR, FAT, TEM and UNG. Don't let them checked partially.
        if ($dayCode == 'Sun') { // Sunday = Ahad
          // d(date('Y-m-d D', strtotime(date('Y-m-', strtotime($endDate)) . $x))); die();
          if ($wh->code != 'DUR' && $wh->code != 'FAT' && $wh->code != 'TEM' && $wh->code != 'UNG') {
            continue;
          }
        }

        $reports = $this->site->getProductReports([
          'warehouse_id' => $wh->id, 'start_date' => $startDate, 'end_date' => $endDate
        ]);

        foreach ($rows as $row) { // Filter items first.
          if ($row->active != 1) continue;
          if ($row->warehouses != $wh->name) continue;

          $items[] = $row;
        }

        $checkCount = 0;
        $filteredItems = [];

        foreach ($items as $item) {
          $isNewItem = FALSE; // New item is not allowed.

          if (!empty($item->purchased_at)) {
            $isNewItem = (date('j', strtotime($item->purchased_at)) > $x ? TRUE : FALSE);
          }

          foreach ($reports as $report) {
            $isTimeEqual = (date('j', strtotime($report->created_at)) == $x);
            $needCheck   = ($isTimeEqual && !$isNewItem);

            if ($report->product_id == $item->product_id && $needCheck) {
              $checkCount++;
              break;
            }
          }

          if (!$isNewItem) $filteredItems[] = $item;
        }

        $itemTotal = count($filteredItems);

        if (!$checkCount) {
          $sheet->setCellValue($A1DateGrid[$x] . $r, 'X'); // Not checked.
        } else if ($itemTotal == $checkCount) {
          $sheet->setCellValue($A1DateGrid[$x] . $r, 'V'); // Fully checked.
        } else {
          // $sheet->setCellValue($A1DateGrid[$x] . $r, 'P'); // Partial checked.
          $sheet->setCellValue($A1DateGrid[$x] . $r, 'V'); // Partial checked.
        }
      }

      $r++;
    }

    goto skip_1;
    skip_1:

    // Machine Report (PASSED)

    $sheet->getSheetByName('Sheet2');
    $sheet->setTitle('Machine Report');

    $r = 2;

    foreach ($rows as $row) {
      // if ($row->product_code != 'PCA2') continue; // TEMP

      $reportBegin = '';
      $reportEnd = date('Y-m-d H:i:s');

      if (!empty($row->assigned_at)) { // If TS assigned, use assigned at as begin report date.
        $reportBegin = $row->assigned_at;
      }

      $duration = ($reportBegin && $reportEnd ? getDaysInPeriod($reportBegin, $reportEnd) : '-');
      // if ($duration < 0) $duration = -1;

      $sheet->setCellValue('A' . $r, $r - 1);
      $sheet->setCellValue('B' . $r, $row->product_code);
      $sheet->setCellValue('C' . $r, $row->product_name);
      $sheet->setCellValue('D' . $r, $row->sn);
      $sheet->setCellValue('E' . $r, $row->category_name);
      $sheet->setCellValue('F' . $r, $row->subcategory_name);
      $sheet->setCellValue('G' . $r, $row->priority);
      $sheet->setCellValue('H' . $r, $row->order_date);
      $sheet->setCellValue('I' . $r, $row->order_price);
      $sheet->setCellValue('J' . $r, $row->disposal_date);
      $sheet->setCellValue('K' . $r, $row->disposal_price);
      $sheet->setCellValue('L' . $r, ($row->active ? 'Active' : 'Inactive'));
      $sheet->setCellValue('M' . $r, $row->warehouses);
      $sheet->setCellValue('N' . $r, $row->maintenance_qty);
      $sheet->setCellValue('O' . $r, $row->maintenance_cost);
      $sheet->setCellValue('P' . $r, lang($row->last_condition));
      $sheet->setCellValue('Q' . $r, $row->creator_name);
      $sheet->setCellValue('R' . $r, html2Note($row->note));
      $sheet->setCellValue('S' . $r, $row->last_update);
      $sheet->setCellValue('T' . $r, $row->assigned_at);
      $sheet->setCellValue('U' . $r, $row->pic_name);
      $sheet->setCellValue('V' . $r, html2Note($row->pic_note ?? ''));
      $sheet->setCellValue('W' . $r, $duration); // Duration in days

      $colorStatus = NULL;

      switch ($row->last_condition) {
        case 'good':
          $colorStatus = '00FF00';
          break;
        case 'off':
          $colorStatus = 'FF0000';
          break;
        case 'trouble':
          $colorStatus = 'FF8000';
      }

      if ($colorStatus) {
        $sheet->setFillColor('P' . $r, $colorStatus);
      }

      $r++;
    }

    // $sheet->setColumnAutoWidth('A');
    $sheet->setColumnAutoWidth('B');
    // $sheet->setColumnAutoWidth('C');
    $sheet->setColumnAutoWidth('D');
    $sheet->setColumnAutoWidth('E');
    $sheet->setColumnAutoWidth('F');
    $sheet->setColumnAutoWidth('G');
    $sheet->setColumnAutoWidth('H');
    $sheet->setColumnAutoWidth('I');
    $sheet->setColumnAutoWidth('J');
    $sheet->setColumnAutoWidth('K');
    $sheet->setColumnAutoWidth('L');
    $sheet->setColumnAutoWidth('M');
    $sheet->setColumnAutoWidth('N');
    $sheet->setColumnAutoWidth('O');
    $sheet->setColumnAutoWidth('P');
    $sheet->setColumnAutoWidth('Q');
    // $sheet->setColumnAutoWidth('R');
    $sheet->setColumnAutoWidth('S');
    $sheet->setColumnAutoWidth('T');
    $sheet->setColumnAutoWidth('U');
    // $sheet->setColumnAutoWidth('V');
    $sheet->setColumnAutoWidth('W');

    skip_2:
    // Maintenance Logs (PASSED)

    $rows = $this->site->getMaintenanceLogs(['start_date' => $startDate, 'end_date' => $endDate]);

    $sheet->getSheetByName('Sheet3');
    $sheet->setTitle('Maintenance Logs');

    $r = 2;

    foreach ($rows as $row) {
      if (!$row->assigned_by) $row->assigned_by = 0;
      if (!$row->pic_id) $row->pic_id = 0;

      $assigner = $this->site->getUserByID($row->assigned_by);
      $pic = $this->site->getUserByID($row->pic_id);
      $loc = $this->site->getWarehouseByID($row->warehouse_id);
      $item = $this->site->getProductByID($row->product_id);

      $sheet->setCellValue('A' . $r, $item->code);
      $sheet->setCellValue('B' . $r, $item->name);
      $sheet->setCellValue('C' . $r, $row->assigned_at);
      $sheet->setCellValue('D' . $r, ($assigner ? $assigner->fullname : ''));
      $sheet->setCellValue('E' . $r, $row->fixed_at);
      $sheet->setCellValue('F' . $r, ($pic ? $pic->fullname : ''));
      $sheet->setCellValue('G' . $r, $loc->name);
      $sheet->setCellValue('H' . $r, htmlRemove($row->note));

      $r++;
    }

    skip_3:

    $sheet->getSheet(0);
    $sheet->setCellValue('A1', date('F Y', strtotime($startDate)));
    $sheet->setBold('A1');

    $name = $this->session->userdata('fullname');

    $sheet->export('PrintERP-MachinePerformance-' . date('Ymd_His') . "-($name)");
  }

  public function sales_status()
  {
    $this->sma->checkPermissions('sales');
    $this->data['product_categories'] = $this->site->getCategories();
    $this->data['error']              = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $this->data['warehouses']         = $this->site->getAllWarehouses();
    $this->data['billers']            = $this->site->getAllBillers();
    $bc                               = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('reports'), 'page' => lang('reports')],
      ['link' => '#', 'page' => lang('sales_status')]
    ];
    $meta = ['page_title' => lang('sales_status'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('reports/sales_status', $this->data);
  }

  // public function suggestions()
  // {
  //   $term = $this->input->get('term', true);
  //   if (strlen($term) < 1) {
  //     die();
  //   }

  //   $rows = $this->reports_model->getProductNames($term, 10); // $term, $limit = 5
  //   if ($rows) {
  //     foreach ($rows as $row) {
  //       $pr[] = ['id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')'];
  //     }
  //     sendJSON($pr);
  //   } else {
  //     echo false;
  //   }
  // }

  public function trackingPODs()
  {
    // die("<b>Sedang Maintenance</b>");
    $startDate = ($this->input->get('start_date') ?? date('Y-m-') . '01');
    $endDate   = ($this->input->get('end_date') ?? date('Y-m-d'));

    $sheet = $this->ridintek->spreadsheet();

    $sheet->loadFile(FCPATH . 'files/templates/TrackingPOD_Report.xlsx');

    // Tracking POD
    $sheet->getSheetByName('Sheet1');
    $sheet->setTitle('Tracking POD');

    $r = 2;

    $tracks = $this->site->getTrackingPODs(['start_date' => $startDate, 'end_date' => $endDate]);

    foreach ($tracks as $track) {
      $klikpod = $this->site->getProductByID($track->pod_id);
      $pic = $this->site->getUserByID($track->created_by);
      $warehouse = $this->site->getWarehouseByID($track->warehouse_id);

      // Convert machine reject to minus.
      $mcReject = ($track->mc_reject > 0 ? $track->mc_reject * -1 : $track->mc_reject);
      $opReject = ($track->op_reject > 0 ? 0 : $track->op_reject);

      $sheet->setCellValue('A' . $r, $track->created_at);
      $sheet->setCellValue('B' . $r, $klikpod->code);
      $sheet->setCellValue('C' . $r, $track->start_click);
      $sheet->setCellValue('D' . $r, $track->end_click);
      $sheet->setCellValue('E' . $r, $track->usage_click);
      $sheet->setCellValue('F' . $r, $mcReject);
      $sheet->setCellValue('G' . $r, $opReject); // op_reject
      $sheet->setCellValue('H' . $r, "=F{$r}+G{$r}"); // total_reject
      $sheet->setCellValue('I' . $r, $track->erp_click);
      $sheet->setCellValue('J' . $r, $track->tolerance);
      $sheet->setCellValue('K' . $r, "=ROUND(H{$r}*0.01*J{$r},0)"); // tolerance_click
      $sheet->setCellValue('L' . $r, $track->cost_click);
      $sheet->setCellValue('M' . $r, "=H{$r}-K{$r}"); // balance
      $sheet->setCellValue('N' . $r, "=IF(M{$r}<0,M{$r}*L{$r},0)"); // total_penalty
      $sheet->setCellValue('O' . $r, $pic->fullname);
      $sheet->setCellValue('P' . $r, $warehouse->name);
      $sheet->setCellValue('Q' . $r, htmlRemove($track->note));

      $r++;
    }

    // Daily Report
    $sheet->getSheetByName('Sheet2');
    $sheet->setTitle('Daily Report');
    $sheet->setCellValue('A1', date('F Y', strtotime($startDate)));

    $A1DateGrid = [
      NULL, 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
      'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ'
    ];

    $lastDate = date('j', strtotime($endDate));

    $pg = 20000;
    $r = 4;
    $warehouses = $this->site->getWarehouses();

    foreach ($warehouses as $wh) {
      if ($wh->active == 0)   continue;
      if ($wh->code == 'ADV') continue;
      if ($wh->code == 'BAL') continue;
      if ($wh->code == 'LUC') continue;

      $sheet->setCellValue('A' . $r, $wh->name);
      $sheet->setCellValue('C' . $r, "=COUNTIF(F{$r}:AJ{$r},\"=X\")");
      $sheet->setCellValue('D' . $r, "=IF(C{$r}=0,\$C\$1*{$pg}/LEFT(\$B\$2,SEARCH(\":\",\$B\$2)-1),-C{$r}*{$pg})");

      $tracks = $this->site->getTrackingPODs([
        'warehouse_id' => $wh->id,
        'start_date' => $startDate,
        'end_date' => $endDate
      ]);

      for ($x = 1; $x <= $lastDate; $x++) {
        $isChecked = FALSE;
        $dayCode = date('D', strtotime(date('Y-m-', strtotime($endDate)) . $x));

        // On Sunday, for DUR, FAT, TEM and UNG only.
        if ($dayCode == 'Sun') {
          if ($wh->code != 'DUR' && $wh->code != 'FAT' && $wh->code != 'TEM' && $wh->code != 'UNG') {
            continue;
          }
        }

        foreach ($tracks as $track) {
          if (date('j', strtotime($track->created_at)) == $x) {
            $isChecked = TRUE;
            break;
          }
        }

        if ($isChecked) {
          $sheet->setCellValue($A1DateGrid[$x] . $r, 'V');
        } else {
          $sheet->setCellValue($A1DateGrid[$x] . $r, 'X');
        }
      }

      $r++;
    }

    // Outlet Penalty
    $sheet->getSheetByName('Sheet3');
    $sheet->setTitle('Outlet Penalty');
    $sheet->setCellValue('A1', date('F Y', strtotime($startDate)));

    $pgKlikPOD   = 1000;
    $pgKlikPODBW = 300;

    $klikpod   = $this->site->getProductByCode('KLIKPOD');
    $klikpodbw = $this->site->getProductByCode('KLIKPODBW');

    $r = 4;

    foreach ($warehouses as $wh) {
      if ($wh->active == 0)   continue;
      if ($wh->code == 'ADV') continue;
      if ($wh->code == 'BAL') continue;
      if ($wh->code == 'LUC') continue;

      $sheet->setCellValue('A' . $r, $wh->name);

      $trackPODs = $this->site->getTrackingPODs([
        'pod_id' => $klikpod->id,
        'warehouse_id' => $wh->id,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'order' => ['created_at', 'ASC']
      ]);

      $trackPODBWs = $this->site->getTrackingPODs([
        'pod_id' => $klikpodbw->id,
        'warehouse_id' => $wh->id,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'order' => ['created_at', 'ASC']
      ]);

      $penaltyPOD   = 0;
      $penaltyPODBW = 0;

      foreach ($trackPODs as $trackPOD) {
        $penaltyPOD += $trackPOD->total_penalty;
      }

      foreach ($trackPODBWs as $trackPODBW) {
        $penaltyPODBW += $trackPODBW->total_penalty;
      }

      if ($trackPODs) {
        $startPOD = $trackPODs[0]->start_click;
        $endPOD = $trackPODs[count($trackPODs) - 1]->end_click;
      } else {
        $startPOD = 0;
        $endPOD = 0;
      }

      if ($trackPODBWs) {
        $startPODBW = $trackPODBWs[0]->start_click;
        $endPODBW = $trackPODBWs[count($trackPODBWs) - 1]->end_click;
      } else {
        $startPODBW = 0;
        $endPODBW = 0;
      }

      $klikPODERP = 0;
      $klikPODBWERP = 0;
      $klikPODs   = $this->site->getStocks([
        'product_id' => $klikpod->id,
        'warehouse_id' => $wh->id,
        'start_date' => $startDate,
        'end_date' => $endDate
      ]);
      $klikPODBWs = $this->site->getStocks([
        'product_id' => $klikpodbw->id,
        'warehouse_id' => $wh->id,
        'start_date' => $startDate,
        'end_date' => $endDate
      ]);

      foreach ($klikPODs as $klikPOD) {
        if ($klikPOD->sale_id > 0) $klikPODERP += $klikPOD->quantity;
        // if ($klikPOD->status == 'received') $klikPODERP += $klikPOD->quantity;
        // if ($klikPOD->status == 'sent') $klikPODERP -= $klikPOD->quantity;
      }

      foreach ($klikPODBWs as $klikPODBW) {
        if ($klikPODBW->sale_id > 0) $klikPODBWERP += $klikPODBW->quantity;
        // if ($klikPODBW->status == 'received') $klikPODBWERP += $klikPODBW->quantity;
        // if ($klikPODBW->status == 'sent') $klikPODBWERP -= $klikPODBW->quantity;
      }

      $sheet->setCellValue('B' . $r, $startPOD); // start click
      $sheet->setCellValue('C' . $r, $endPOD); // end click
      $sheet->setCellValue('D' . $r, "=C{$r}-B{$r}"); // usage
      $sheet->setCellValue('E' . $r, $klikPODERP); // erp click
      $sheet->setCellValue('F' . $r, $penaltyPOD); // Subtotal Penalty
      $sheet->setCellValue('G' . $r, $startPODBW); // start click bw
      $sheet->setCellValue('H' . $r, $endPODBW); // end click bw
      $sheet->setCellValue('I' . $r, "=H{$r}-G{$r}"); // usage bw
      $sheet->setCellValue('J' . $r, $klikPODBWERP); // erp click bw
      $sheet->setCellValue('K' . $r, $penaltyPODBW); // Subtotal Penalty bw
      $sheet->setCellValue('L' . $r, "=F{$r}+K{$r}"); // Amount Penalty

      $r++;
    }

    // User Penalty
    $sheet->getSheetByName('Sheet4');
    $sheet->setTitle('User Penalty');
    $sheet->setCellValue('A1', date('F Y', strtotime($startDate)));

    $pgKlikPOD   = 1000;
    $pgKlikPODBW = 300;

    $klikpod   = $this->site->getProductByCode('KLIKPOD');
    $klikpodbw = $this->site->getProductByCode('KLIKPODBW');

    $userTracks = $this->site->getTrackingPODUsers(['start_date' => $startDate, 'end_date' => $endDate]);

    $r = 4;

    foreach ($userTracks as $userTrack) {
      $user = $this->site->getUserByID($userTrack->created_by);
      $tracks = $this->site->getTrackingPODs([
        'created_by' => $user->id,
        'start_date' => $startDate,
        'end_date'   => $endDate
      ]);

      $sheet->setCellValue('A' . $r, $user->fullname);

      $usageKlikPOD      = 0;
      $usageKlikPODBW    = 0;
      $mcRejectKlikPOD   = 0;
      $opRejectKlikPOD   = 0;
      $mcRejectKlikPODBW = 0;
      $opRejectKlikPODBW = 0;
      $balanceKlikPOD    = 0;
      $balanceKlikPODBW  = 0;

      foreach ($tracks as $track) {
        if ($track->pod_id == $klikpod->id) {
          $usageKlikPOD += filterDecimal($track->usage_click);
          $mcRejectKlikPOD += filterDecimal($track->mc_reject);
          $opRejectKlikPOD += filterDecimal($track->op_reject);
          $balanceKlikPOD += filterDecimal($track->balance);
        }

        if ($track->pod_id == $klikpodbw->id) {
          $usageKlikPODBW += filterDecimal($track->usage_click);
          $mcRejectKlikPODBW += filterDecimal($track->mc_reject);
          $opRejectKlikPODBW += filterDecimal($track->op_reject);
          $balanceKlikPODBW += filterDecimal($track->balance);
        }
      }

      $sheet->setCellValue('B' . $r, $usageKlikPOD);
      $sheet->setCellValue('C' . $r, $mcRejectKlikPOD);
      $sheet->setCellValue('D' . $r, $opRejectKlikPOD);
      $sheet->setCellValue('E' . $r, "=C{$r}+D{$r}");
      $sheet->setCellValue('F' . $r, $balanceKlikPOD); // Balance
      $sheet->setCellValue('G' . $r, "=IF(F{$r}<0,F{$r}*{$pgKlikPOD},0)"); // Subtotal Penalty
      $sheet->setCellValue('H' . $r, $usageKlikPODBW);
      $sheet->setCellValue('I' . $r, $mcRejectKlikPODBW);
      $sheet->setCellValue('J' . $r, $opRejectKlikPODBW);
      $sheet->setCellValue('K' . $r, "=I{$r}+J{$r}");
      $sheet->setCellValue('L' . $r, $balanceKlikPODBW); // Balance
      $sheet->setCellValue('M' . $r, "=IF(L{$r}<0,L{$r}*{$pgKlikPODBW},0)"); // Subtotal Penalty
      $sheet->setCellValue('N' . $r, "=F{$r}+L{$r}"); // Total Balance
      $sheet->setCellValue('O' . $r, "=IF(N{$r}<0,G{$r}+M{$r},0)"); // If K{$r} < 0 then penalty else 0.

      $r++;
    }

    $sheet->getSheetByName('Tracking POD'); // 1st
    $sheet->setColumnAutoWidth('A');
    $sheet->setColumnAutoWidth('P');

    $sheet->getSheetByName('Outlet Penalty'); // 4th
    $sheet->setCellValue('A1', date('F Y', strtotime($startDate)));
    $sheet->setBold('A1');

    $name = $this->session->userdata('fullname');

    $sheet->export('PrintERP-TrackingPOD-' . date('Ymd_His') . "-($name)");
  }
}
/* EOF */