<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Web\Client as Client;
use App\Http\Controllers\Web\Admin as Admin;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require_once __DIR__ . '/jetstream.php';


Route::group(['middleware' => ['read.only', 'check.system']], function () {

    /*
    * Maintenance Static Pages
    */
    Route::get('maintenance', [Client\PageController::class, 'maintenance'])->name('page.maintenance');

    Route::group(['middleware' => ['language.detect', 'maintenance', 'ping']], function () {

        Route::get('/', function () {
            return Inertia::render('Dashboard');
        })->name('home');

        /*
         * Public Market Pages
         */
        Route::get('markets', [Client\MarketController::class, 'index'])->name('markets');
        Route::get('market/{market:name}', [Client\MarketController::class, 'show'])->name('market');

        /*
         * Public Chart Page
         */
        Route::group(['middleware' => ['throttle:chart']], function () {
            Route::get('tradingview-chart/config', [Client\ChartController::class, 'config'])->name('chart.config');
            Route::get('tradingview-chart/symbols', [Client\ChartController::class, 'symbols'])->name('chart.symbols');
            Route::get('tradingview-chart/chart/{symbol}', [Client\ChartController::class, 'index'])->name('chart');
            Route::get('tradingview-chart/time', [Client\ChartController::class, 'time'])->name('chart.time');
            Route::get('tradingview-chart/history', [Client\ChartController::class, 'history'])->name('chart.history');
            Route::get('tradingview-chart', [Client\ChartController::class, 'candles'])->name('chart.candles');
        });

        /*
         * Settings Controller
         */
        Route::get('settings/mode', [Client\SettingsController::class, 'mode'])->name('settings.theme.mode');

        /*
         * Public Static Pages
         */
        Route::get('page/{slug}', [Client\PageController::class, 'show'])->name('page.show');

        /*
         * Language Loader
         */
        Route::get('language/load', [Client\LanguageController::class, 'index'])->name('language.load');
        Route::get('language/set', [Client\LanguageController::class, 'set'])->name('language.set');

        Route::group(['prefix' => 'exchange-control-panel'], function () {
            Route::get('admin-login', [Admin\DashboardController::class, 'login'])->name('admin.login');
        });

        Route::group(['middleware' => ['verified']], function () {

            Route::get('user/kyc', [Client\KycDocumentController::class, 'index'])->name('user.kyc');
            Route::post('user/kyc', [Client\KycDocumentController::class, 'store'])->name('user.kyc.store');

            Route::group(['middleware' => ['throttle:upload']], function () {
                Route::post('/upload', [Client\FileController::class, 'upload'])->name('user-file-upload');
                Route::delete('/upload', [Client\FileController::class, 'delete'])->name('user-file-delete');
            });

            Route::group(['middleware' => ['kyc.verified']], function () {

                // Wallets Page
                Route::get('wallets', [Client\WalletController::class, 'index'])->name('wallets');

                Route::get('wallets/deposit/crypto/{symbol}', [Client\WalletController::class, 'depositCrypto'])->name('wallets.deposit.crypto');

                Route::get('wallets/deposit/fiat/success', [Client\WalletController::class, 'depositFiatSuccess'])->name('wallets.deposit.fiat.success');
                Route::get('wallets/deposit/fiat/{symbol}', [Client\WalletController::class, 'depositFiat'])->name('wallets.deposit.fiat');

                Route::get('wallets/withdraw/crypto/success', [Client\WalletController::class, 'withdrawCryptoSuccess'])->name('wallets.withdraw.crypto.success');
                Route::get('wallets/withdraw/crypto/{symbol}', [Client\WalletController::class, 'withdrawCrypto'])->name('wallets.withdraw.crypto');

                Route::get('wallets/withdraw/fiat/success', [Client\WalletController::class, 'withdrawFiatSuccess'])->name('wallets.withdraw.fiat.success');
                Route::get('wallets/withdraw/fiat/{symbol}', [Client\WalletController::class, 'withdrawFiat'])->name('wallets.withdraw.fiat');

                Route::post('wallets/deposit/store/fiat', [Client\WalletController::class, 'depositStore'])->name('wallets.deposit.store.bank');
                Route::post('wallets/withdraw/store/fiat', [Client\WalletController::class, 'withdrawStore'])->name('wallets.withdraw.store.fiat');

                // Orders Page
                Route::get('orders', [Client\OrderController::class, 'index'])->name('orders');

                // Reports Page
                Route::get('reports/deposits', [Client\ReportController::class, 'deposits'])->name('reports.deposits');
                Route::get('reports/fiat-deposits', [Client\ReportController::class, 'fiatDeposits'])->name('reports.deposits.fiat');

                Route::get('reports/withdrawals', [Client\ReportController::class, 'withdrawals'])->name('reports.withdrawals');
                Route::get('reports/fiat-withdrawals', [Client\ReportController::class, 'fiatWithdrawals'])->name('reports.withdrawals.fiat');

                Route::get('reports/trades', [Client\ReportController::class, 'trades'])->name('reports.trades');
                Route::get('orders/order-history', [Client\ReportController::class, 'orderHistory'])->name('reports.order-history');
                Route::get('reports/referral-transactions', [Client\ReportController::class, 'referralTransactions'])->name('reports.referral-transactions');

                Route::middleware('auth:sanctum')->get('/user', function (\Illuminate\Http\Request $request) {
                    return $request->user();
                });

                Route::group(['prefix' => 'exchange-control-panel', 'middleware' => ['role:admin']], function () {

                    Route::get('', [Admin\DashboardController::class, 'index'])->name('admin.dashboard');

                    /*
                     * Markets
                     */
                    Route::get('markets', [Admin\MarketController::class, 'index'])->name('admin.markets');
                    Route::get('markets/create', [Admin\MarketController::class, 'create'])->name('admin.markets.create');
                    Route::post('markets', [Admin\MarketController::class, 'store'])->name('admin.markets.store');
                    Route::get('markets/{market}/edit', [Admin\MarketController::class, 'edit'])->name('admin.markets.edit');
                    Route::put('markets/{market}', [Admin\MarketController::class, 'update'])->name('admin.markets.update');
                    Route::delete('markets/{market}', [Admin\MarketController::class, 'destroy'])->name('admin.markets.destroy');
                    Route::put('markets/{market}/restore', [Admin\MarketController::class, 'restore'])->name('admin.markets.restore');

                    /*
                     * Currencies
                     */
                    Route::get('currencies', [Admin\CurrencyController::class, 'index'])->name('admin.currencies');
                    Route::get('currencies/create', [Admin\CurrencyController::class, 'create'])->name('admin.currencies.create');
                    Route::post('currencies', [Admin\CurrencyController::class, 'store'])->name('admin.currencies.store');
                    Route::get('currencies/{currency}/edit', [Admin\CurrencyController::class, 'edit'])->name('admin.currencies.edit');
                    Route::put('currencies/{currency}', [Admin\CurrencyController::class, 'update'])->name('admin.currencies.update');
                    Route::delete('currencies/{currency}', [Admin\CurrencyController::class, 'destroy'])->name('admin.currencies.destroy');
                    Route::put('currencies/{currency}/restore', [Admin\CurrencyController::class, 'restore'])->name('admin.currencies.restore');
                    Route::get('currencies/coinpayments/coins', [Admin\CurrencyController::class, 'getCoinpaymentsCoins'])->name('admin.currencies.coinpayments.coins');
                    Route::get('currencies/coinpayments/sync', [Admin\CurrencyController::class, 'syncCoinpaymentsCoins'])->name('admin.currencies.coinpayments.sync');

                    /*
                     * Networks
                     */
                    Route::get('networks', [Admin\NetworkController::class, 'index'])->name('admin.networks');
                    Route::get('networks/{network}/edit', [Admin\NetworkController::class, 'edit'])->name('admin.networks.edit');
                    Route::put('networks/{network}', [Admin\NetworkController::class, 'update'])->name('admin.networks.update');

                    /*
                     * Users
                     */
                    Route::get('users', [Admin\UserController::class, 'index'])->name('admin.users');
                    Route::get('users/{user}/edit', [Admin\UserController::class, 'edit'])->name('admin.users.edit');
                    Route::put('users/{user}', [Admin\UserController::class, 'update'])->name('admin.users.update');
                    Route::delete('users/{user}', [Admin\UserController::class, 'destroy'])->name('admin.users.destroy');

                    /*
                     * Kyc Documents
                     */
                    Route::get('kyc-documents', [Admin\KycDocumentController::class, 'index'])->name('admin.kyc.documents');
                    Route::put('kyc-documents/{document}', [Admin\KycDocumentController::class, 'moderate'])->name('admin.kyc.moderate');

                    /*
                     * Settings
                     */
                    Route::get('settings', [Admin\SettingsController::class, 'index'])->name('admin.settings');
                    Route::put('settings', [Admin\SettingsController::class, 'update'])->name('admin.settings.update');

                    /*
                     * System Monitor
                     */
                    Route::get('system-monitor/test', [Admin\SystemMonitorController::class, 'test'])->name('admin.system.monitor.test');
                    Route::post('system-monitor/websocket', [Admin\SystemMonitorController::class, 'websocket'])->name('admin.system.monitor.websocket');

                    /*
                     * Pages
                     */
                    Route::get('pages', [Admin\PageController::class, 'index'])->name('admin.pages');
                    Route::get('pages/create', [Admin\PageController::class, 'create'])->name('admin.pages.create');
                    Route::post('pages', [Admin\PageController::class, 'store'])->name('admin.pages.store');
                    Route::get('pages/{page}/edit', [Admin\PageController::class, 'edit'])->name('admin.pages.edit');
                    Route::put('pages/{page}', [Admin\PageController::class, 'update'])->name('admin.pages.update');
                    Route::delete('pages/{page}', [Admin\PageController::class, 'destroy'])->name('admin.pages.destroy');

                    /*
                     * Bank Accounts
                     */
                    Route::get('bank-accounts', [Admin\BankAccountController::class, 'index'])->name('admin.bank_accounts');
                    Route::get('bank-accounts/create', [Admin\BankAccountController::class, 'create'])->name('admin.bank_accounts.create');
                    Route::post('bank-accounts', [Admin\BankAccountController::class, 'store'])->name('admin.bank_accounts.store');
                    Route::get('bank-accounts/{bankAccount}/edit', [Admin\BankAccountController::class, 'edit'])->name('admin.bank_accounts.edit');
                    Route::put('bank-accounts/{bankAccount}', [Admin\BankAccountController::class, 'update'])->name('admin.bank_accounts.update');
                    Route::delete('bank-accounts/{bankAccount}', [Admin\BankAccountController::class, 'destroy'])->name('admin.bank_accounts.destroy');

                    /*
                     * Languages
                     */
                    Route::get('languages', [Admin\LanguageController::class, 'index'])->name('admin.languages');
                    Route::get('languages/create', [Admin\LanguageController::class, 'create'])->name('admin.languages.create');
                    Route::post('languages', [Admin\LanguageController::class, 'store'])->name('admin.languages.store');
                    Route::get('languages/{language}/edit', [Admin\LanguageController::class, 'edit'])->name('admin.languages.edit');
                    Route::put('languages/{language}', [Admin\LanguageController::class, 'update'])->name('admin.languages.update');
                    Route::delete('languages/{language}', [Admin\LanguageController::class, 'destroy'])->name('admin.languages.destroy');

                    /*
                     * Language Translations
                     */
                    Route::post('languages-translations/sync/{language}', [Admin\LanguageController::class, 'sync'])->name('admin.language.translations.sync');
                    Route::get('languages-translations/{language}', [Admin\LanguageController::class, 'translations'])->name('admin.language.translations');
                    Route::put('languages-translations/store/{language}', [Admin\LanguageController::class, 'translationsStore'])->name('admin.language.translations.store');
                    Route::put('languages-translations/update/{language}', [Admin\LanguageController::class, 'translationsUpdate'])->name('admin.language.translations.update');
                    Route::delete('languages-translations/{language}/{translation}', [Admin\LanguageController::class, 'translationsDestroy'])->name('admin.language.translations.destroy');

                    /*
                     * Reports
                     */
                    Route::get('reports', [Admin\ReportController::class, 'index'])->name('admin.reports');
                    Route::get('reports/deposits', [Admin\ReportController::class, 'deposits'])->name('admin.reports.deposits');
                    Route::get('reports/fiat-deposits', [Admin\ReportController::class, 'fiatDeposits'])->name('admin.reports.deposits.fiat');
                    Route::get('reports/withdrawals', [Admin\ReportController::class, 'withdrawals'])->name('admin.reports.withdrawals');
                    Route::get('reports/fiat-withdrawals', [Admin\ReportController::class, 'fiatWithdrawals'])->name('admin.reports.withdrawals.fiat');

                    Route::put('reports/withdrawals/{withdrawal}', [Admin\ReportController::class, 'moderateWithdrawal'])->name('admin.reports.withdrawals.moderate');
                    Route::get('reports/trades', [Admin\ReportController::class, 'trades'])->name('admin.reports.trades');
                    Route::get('reports/referral-transactions', [Admin\ReportController::class, 'referralTransactions'])->name('admin.reports.referral-transactions');

                    Route::put('reports/fiat-deposits/{deposit}', [Admin\ReportController::class, 'moderateFiatDeposit'])->name('admin.reports.deposits.fiat.moderate');
                    Route::put('reports/fiat-withdrawals/{withdrawal}', [Admin\ReportController::class, 'moderateFiatWithdrawal'])->name('admin.reports.withdrawals.fiat.moderate');

                    Route::get('reports/wallets/system', [Admin\ReportController::class, 'systemWallets'])->name('admin.reports.wallets.system');
                    Route::get('reports/wallets', [Admin\ReportController::class, 'wallets'])->name('admin.reports.wallets');

                    /*
                    * File Upload
                    */
                    Route::post('/upload', [Admin\FileController::class, 'upload'])->name('file-upload');
                    Route::delete('/upload', [Admin\FileController::class, 'delete'])->name('file-delete');
                });
            });
        });
    });

    Route::get('license', [Client\SystemMonitorController::class, 'index'])->name('system-monitor.ping');
    Route::post('license', [Client\SystemMonitorController::class, 'register'])->name('system-monitor.ping.register');

});
