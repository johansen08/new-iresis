<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = 'welcome/page_notfound';
$route['translate_uri_dashes'] = FALSE;

$route['auth'] = 'login/auth';

$route['logout'] = 'welcome/logout';

$route['receipt/save-receipt'] = 'receipt/save_receipt';
$route['receipt/scan-combined'] = 'receipt/scan_combined';
$route['receipt/save-combined'] = 'receipt/save_combined';
$route['receipt/print-label/(:any)'] = 'receipt/print_label/$1';
$route['receipt/detail-receipt'] = 'receipt/detail_receipt';
$route['receipt/get-list-receipt-data'] = 'receipt/get_list_receipt_data';
$route['receipt/delete-list-receipt-data/(:any)'] = 'receipt/delete_list_receipt_data/$1';
$route['receipt/delete-receipt-action'] = 'receipt/delete_receipt_action';
$route['receipt/save-reprint-receipt'] = 'receipt/save_reprint_receipt';
$route['receipt/upload-receipt-action'] = 'receipt/upload_receipt_action';

$route['receipt/upload-sku'] = 'sku/upload_sku';
$route['sku/upload-sku'] = 'sku/upload_sku';
$route['sku/upload_sku'] = 'sku/upload_sku';
$route['sku/upload-sku-action'] = 'sku/upload_sku_action';
$route['sku/upload_sku_action'] = 'sku/upload_sku_action';
$route['sku/get-progress-file/(:any)'] = 'sku/get_progress_file/$1';
$route['sku/get_progress_file/(:any)'] = 'sku/get_progress_file/$1';

$route['picker/scan-picker'] = 'picker/scan_picker';
$route['picker/save-scan-picker'] = 'picker/save_scan_picker';
$route['picker/scan-picker-preorder'] = 'picker/scan_picker_preorder';
$route['picker/save-scan-picker-preorder'] = 'picker/save_scan_picker_preorder';
$route['picker/search_picker'] = 'picker/search_picker';
$route['picker/get-search-picker-data'] = 'picker/get_search_picker_data';
$route['picker/master_picker'] = 'picker/master_picker';
$route['picker/save-master-picker'] = 'picker/save_master_picker';
$route['picker/delete_master_picker'] = 'picker/delete_master_picker';
$route['picker/pending_picker'] = 'picker/pending_picker';
$route['picker/save-pending-picker'] = 'picker/save_pending_picker';
$route['picker/update_picker'] = 'picker/update_picker';
$route['picker/save-update-picker'] = 'picker/save_update_picker';
$route['picker/kurangan-picker'] = 'picker/kurangan_picker';
$route['picker/save-kurangan-picker'] = 'picker/save_kurangan_picker';
$route['picker/get-kurangan-picker-data'] = 'picker/get_kurangan_picker_data';
$route['picker/get-kurangan-picker-data/(:any)'] = 'picker/get_kurangan_picker_data/$1';

$route['packer/scan-packer'] = 'packer/scan_packer';
$route['packer/scan-packer-webcam'] = 'packer/scan_packer_webcam';
$route['packer/scan_packer_webcam'] = 'packer/scan_packer_webcam';
$route['packer/save-packer'] = 'packer/save_packer';
$route['packer/get-data-packer'] = 'packer/get_data_packer';
$route['packer/get-scan-packer-data/(:any)'] = 'packer/get_scan_packer_data/$1';
$route['packer/masalah-picker-save'] = 'packer/masalah_picker_save';
$route['packer/masalah-packer'] = 'packer/masalah_packer';
$route['packer/get-masalah-packer-data'] = 'packer/get_masalah_packer_data';
$route['packer/kurangan-packer'] = 'packer/kurangan_packer';
$route['packer/get-picker-list'] = 'packer/get_picker_list';
$route['packer/cancel-masalah-packer'] = 'packer/cancel_masalah_packer';
$route['packer/scan-packer-nosubmit'] = 'packer/scan_packer_nonsubmit';
$route['packer/scan_packer_nosubmit'] = 'packer/scan_packer_nonsubmit';
$route['packer/save-packer-nosubmit'] = 'packer/save_packer_nonsubmit';
$route['packer/save_packer_nosubmit'] = 'packer/save_packer_nonsubmit';
$route['packer/save-packer-nonsubmit'] = 'packer/save_packer_nonsubmit';
$route['packer/save_packer_nonsubmit'] = 'packer/save_packer_nonsubmit';

$route['packer_monitoring/update_session'] = 'packer_monitoring/update_session';
$route['packer-monitoring/update-session'] = 'packer_monitoring/update_session';
$route['packer_monitoring/get_monitoring_data'] = 'packer_monitoring/get_monitoring_data';
$route['packer_monitoring/get_detailed_logs/(:any)'] = 'packer_monitoring/get_detailed_logs/$1';

$route['handover/save-handover'] = 'handover/save_handover';
$route['handover/get-data-handover'] = 'handover/get_data_handover';

$route['lost_scan_packer/input'] = 'lost_scan_packer/input';
$route['lost_scan_packer/input/(:any)'] = 'lost_scan_packer/input/$1';
$route['lost_scan_packer/report'] = 'lost_scan_packer/report';
$route['lost_scan_packer/save'] = 'lost_scan_packer/save';
$route['lost_scan_packer/get_lost_scan_data'] = 'lost_scan_packer/get_lost_scan_data';
$route['lost_scan_packer/export_excel'] = 'lost_scan_packer/export_excel';

$route['retur/scan-retur'] = 'retur/scan_retur';
$route['retur/update-retur'] = 'retur/update_retur';
$route['retur/update-retur-komplain'] = 'retur/update_retur_komplain';
$route['retur/scan-retur-komplain'] = 'retur/scan_retur_komplain';
$route['retur/verifikasi-retur-komplain'] = 'retur/verifikasi_retur_komplain';
$route['retur/upload-retur-jubelio'] = 'retur/upload_retur_jubelio';
$route['retur/save-retur'] = 'retur/save_retur';
$route['retur/save-buka-retur'] = 'retur/save_buka_retur';
$route['retur/save-buka-retur-sku'] = 'retur/save_buka_retur_sku';
$route['retur/save-buka-retur-sku-bulk'] = 'retur/save_buka_retur_sku_bulk';
$route['retur/get-buka-retur-sku-data/(:any)'] = 'retur/get_buka_retur_sku_data/$1';
$route['retur/search-retur'] = 'retur/search_retur';
$route['retur/get-data-retur'] = 'retur/get_data_retur';
$route['retur/laporan-retur'] = 'retur/laporan_retur';
$route['retur/laporan-retur-komplain'] = 'retur/laporan_retur_komplain';
$route['retur/get-data-terima-retur-laporan'] = 'retur/get_data_terima_retur_laporan';
$route['retur/get-data-buka-retur-laporan'] = 'retur/get_data_buka_retur_laporan';
$route['retur/export-excel-terima-retur'] = 'retur/export_excel_terima_retur';
$route['retur/export-excel-buka-retur'] = 'retur/export_excel_buka_retur';
$route['retur/get-data-laporan-retur-lengkap'] = 'retur/get_data_laporan_retur_lengkap';
$route['retur/export-excel-laporan-retur-lengkap'] = 'retur/export_excel_laporan_retur_lengkap';
$route['retur/laporan-retur-shipped'] = 'retur/laporan_retur_shipped';
$route['retur/get-data-laporan-retur-shipped'] = 'retur/get_data_laporan_retur_shipped';
$route['retur/export-excel-laporan-retur-shipped'] = 'retur/export_excel_laporan_retur_shipped';
$route['retur/cek-sku'] = 'retur/cek_sku';
$route['retur/get-sku-suggestions'] = 'retur/get_sku_suggestions';
$route['retur/get-retur-details-by-sku-api'] = 'retur/get_retur_details_by_sku_api';
$route['retur/kirim-display'] = 'retur/kirim_display';
$route['retur/get-unbatched-returns'] = 'retur/get_unbatched_returns';
$route['retur/create-display-batch'] = 'retur/create_display_batch';
$route['restock/laporan-retur-display'] = 'restock/laporan_retur_display';
$route['restock/get-display-batches'] = 'restock/get_display_batches';
$route['restock/get-display-batch-details'] = 'restock/get_display_batch_details';
$route['restock/confirm-display-batch'] = 'restock/confirm_display_batch';
$route['restock/print-retur-display-batch'] = 'restock/print_retur_display_batch';
$route['retur/dashboard'] = 'retur/dashboard';
$route['retur/get-dashboard-data'] = 'retur/get_dashboard_data';
$route['retur/import-retur'] = 'retur/import_retur';
$route['retur/upload-retur'] = 'retur/upload_retur';
$route['retur/validasi-jubelio'] = 'retur/validasi_jubelio';
$route['retur/upload-jubelio'] = 'retur/upload_jubelio';
$route['retur/get-rekonsiliasi-data'] = 'retur/get_rekonsiliasi_data';
$route['retur/verifikasi-jubelio'] = 'retur/verifikasi_jubelio';
$route['retur/bulk-verifikasi-jubelio'] = 'retur/bulk_verifikasi_jubelio';
$route['retur/get-jubelio-list-data'] = 'retur/get_jubelio_list_data';
$route['retur/export-rekonsiliasi'] = 'retur/export_rekonsiliasi';
$route['retur/rekap-proses-retur'] = 'retur/rekap_proses_retur';
$route['retur/get-rekap-proses-retur'] = 'retur/get_rekap_proses_retur';
$route['retur/get-rekap-detail'] = 'retur/get_rekap_detail';
$route['retur/export-rekap-proses-retur'] = 'retur/export_rekap_proses_retur';

// Klaim retur ke kurir / marketplace (tahap 2)
$route['retur-klaim/pengajuan'] = 'retur_klaim/pengajuan';
$route['retur-klaim/verifikasi'] = 'retur_klaim/verifikasi';
$route['retur-klaim/get-kandidat'] = 'retur_klaim/get_kandidat';
$route['retur-klaim/ajukan'] = 'retur_klaim/ajukan';
$route['retur-klaim/get-klaim-data'] = 'retur_klaim/get_klaim_data';
$route['retur-klaim/simpan-putusan'] = 'retur_klaim/simpan_putusan';
$route['retur-klaim/simpan-pergantian'] = 'retur_klaim/simpan_pergantian';
$route['retur-klaim/simpan-verifikasi'] = 'retur_klaim/simpan_verifikasi';
$route['retur-klaim/batal'] = 'retur_klaim/batal';
$route['retur-klaim/get-stats'] = 'retur_klaim/get_stats';
$route['retur-klaim/get-alarm'] = 'retur_klaim/get_alarm';
$route['retur/progress-status-retur'] = 'retur/progress_status_retur';
$route['retur/complain'] = 'retur/complain';
$route['retur/save-refund-complain'] = 'retur/save_refund_complain';
$route['retur/save-replacement-complain'] = 'retur/save_replacement_complain';
$route['retur/check-complain-exists'] = 'retur/check_complain_exists';
$route['retur/get-complain-data'] = 'retur/get_complain_data';
$route['retur/get-receipt-info'] = 'retur/get_receipt_info';
$route['retur/update-complain-status'] = 'retur/update_complain_status';

$route['report/get-receipt-in-process-data-tab0'] = 'report/get_receipt_in_process_data_tab0';
$route['report/get-receipt-in-process-data-tab1'] = 'report/get_receipt_in_process_data_tab1';
$route['report/get-receipt-in-process-data-tab2'] = 'report/get_receipt_in_process_data_tab2';
$route['report/export-to-excel-receipt-in-process-tab0'] = 'report/export_to_excel_receipt_in_process_tab0';
$route['report/export-to-excel-receipt-in-process-tab1'] = 'report/export_to_excel_receipt_in_process_tab1';
$route['report/export-to-excel-receipt-in-process-tab2'] = 'report/export_to_excel_receipt_in_process_tab2';
$route['report/daily-receipt-report'] = 'report/daily_receipt_report';
$route['report/get-daily-receipt-report-data'] = 'report/get_daily_receipt_report_data';
$route['report/export-to-excel-daily-receipt-report'] = 'report/export_to_excel_daily_receipt_report';
$route['report/per-day-receipt-report'] = 'report/per_day_receipt_report';
$route['report/get-per-day-receipt-report-data'] = 'report/get_per_day_receipt_report_data';
$route['report/export-to-excel-per-day-receipt-report'] = 'report/export_to_excel_per_day_receipt_report';
$route['report/kurangan-picker-processed'] = 'report/kurangan_picker_processed';
$route['report/get-kurangan-picker-processed-data'] = 'report/get_kurangan_picker_processed_data';
$route['report/export-excel-kurangan-picker-processed'] = 'report/export_excel_kurangan_picker_processed';
$route['report/delivery-report-tab0'] = 'report/get_delivery_report_data_tab0';
$route['report/get-receipt-report-data-tab0'] = 'report/get_receipt_report_data_tab0';
$route['report/get-receipt-report-data-tab1'] = 'report/get_receipt_report_data_tab1';
$route['report/export-to-excel-receipt-report-tab0'] = 'report/export_to_excel_receipt_report_tab0';
$route['report/export-to-excel-receipt-report-tab1'] = 'report/export_to_excel_receipt_report_tab1';
$route['report/shipped-receipt-report'] = 'report/shipped_receipt_report';
$route['report/get-shipped-receipt-report-data'] = 'report/get_shipped_receipt_report_data';
$route['report/export-to-excel-shipped-receipt-report'] = 'report/export_to_excel_shipped_receipt_report';
$route['report/shipping-report'] = 'report/shipping_report';
$route['report/export-to-excel-shipping-report'] = 'report/export_to_excel_shipping_report';
$route['report/retur-receipt-report'] = 'report/retur_receipt_report';
$route['report/get-retur-receipt-report-data'] = 'report/get_retur_receipt_report_data';
$route['report/export-to-excel-retur-receipt-report'] = 'report/export_to_excel_retur_receipt_report';
$route['report/preorder-receipt-report'] = 'report/preorder_receipt_report';
$route['report/get-preorder-receipt-report-data'] = 'report/get_preorder_receipt_report_data';
$route['report/export-to-excel-preorder-receipt-report'] = 'report/export_to_excel_preorder_receipt_report';
$route['report/sku-special-report'] = 'report/sku_special_report';
$route['report/get-sku-special-report-data'] = 'report/get_sku_special_report_data';
$route['report/export-to-excel-sku-special-report'] = 'report/export_to_excel_sku_special_report';
$route['report/get-terima-retur-report-data'] = 'report/get_terima_retur_report_data';
$route['report/get-buka-retur-report-data'] = 'report/get_buka_retur_report_data';
$route['report/export-to-excel-terima-retur-report'] = 'report/export_to_excel_terima_retur_report';
$route['report/export-to-excel-buka-retur-report'] = 'report/export_to_excel_buka_retur_report';
$route['report/delete-terima-retur/(:num)'] = 'report/delete_terima_retur/$1';
$route['report/delete-buka-retur/(:num)'] = 'report/delete_buka_retur/$1';

$route['accounting/action-kurangan-picker'] = 'accounting/action_kurangan_picker';
$route['report/get-production-team-report-data-tab0'] = 'report/get_production_team_report_data_tab0';
$route['report/get-production-team-report-data-tab1'] = 'report/get_production_team_report_data_tab1';
$route['report/get-production-team-report-data-tab2'] = 'report/get_production_team_report_data_tab2';
$route['report/export-to-excel-production-team-report-tab0'] = 'report/export_to_excel_production_team_report_tab0';
$route['report/get-picker-performance-detail-summary'] = 'report/get_picker_performance_detail_summary';
$route['report/get-picker-sku-summary'] = 'report/get_picker_sku_summary';
$route['report/get-picker-resi-list-by-sku'] = 'report/get_picker_resi_list_by_sku';
$route['report/export-picker-sku-summary'] = 'report/export_picker_sku_summary';
$route['report/export-picker-resi-list-by-sku'] = 'report/export_picker_resi_list_by_sku';
$route['report/get-packer-performance-detail-summary'] = 'report/get_packer_performance_detail_summary';
$route['report/get-packer-sku-summary'] = 'report/get_packer_sku_summary';
$route['report/get-packer-resi-list-by-sku'] = 'report/get_packer_resi_list_by_sku';
$route['report/export-packer-sku-summary'] = 'report/export_packer_sku_summary';
$route['report/export-packer-resi-list-by-sku'] = 'report/export_packer_resi_list_by_sku';
$route['report/export-to-excel-production-team-report-tab1'] = 'report/export_to_excel_production_team_report_tab1';
$route['report/export-to-excel-production-team-report-tab2'] = 'report/export_to_excel_production_team_report_tab2';

$route['report/resi-cancel-report'] = 'report/resi_cancel_report';
$route['report/get_resi_cancel_report_data'] = 'report/get_resi_cancel_report_data';
$route['report/export-excel-resi-cancel'] = 'report/export_excel_resi_cancel';

$route['kpi_reports'] = 'kpi_reports';
$route['kpi_reports/export'] = 'kpi_reports/export';
$route['kpi_reports/get-kpi-data'] = 'kpi_reports/get_kpi_data';
$route['kpi_reports/export-to-excel'] = 'kpi_reports/export_to_excel';
$route['kpi_reports/export-excel-picker'] = 'kpi_reports/export_excel_picker';
$route['kpi_reports/export-excel-packer'] = 'kpi_reports/export_excel_packer';

$route['kpi/dashboard'] = 'kpi_reports/dashboard';
$route['kpi/dashboard-picker'] = 'kpi_reports/dashboard_picker';
$route['kpi/dashboard-packer'] = 'kpi_reports/dashboard_packer';
$route['kpi/target-kpi'] = 'target_kpi/index';
$route['kpi/target-kpi-picker'] = 'target_kpi/picker';
$route['kpi/target-kpi-packer'] = 'target_kpi/packer';
$route['target_kpi/save-targets'] = 'target_kpi/save_targets';
$route['target_kpi/update-target'] = 'target_kpi/update_target';
$route['target_kpi/delete-target'] = 'target_kpi/delete_target';
$route['target_kpi/copy-targets'] = 'target_kpi/copy_targets';

$route['user'] = 'user';
$route['user/add_user'] = 'user/edit_user';
$route['user/edit_user/(:any)'] = 'user/edit_user/$1';
$route['user/generate_password_user/(:any)'] = 'user/generate_password_user/$1';
$route['user/delete_user/(:any)'] = 'user/delete_user/$1';
$route['user/save_user'] = 'user/save_user';
$route['user/update_password_user'] = 'user/update_password_user';
$route['user/update_role_user'] = 'user/update_role_user';

$route['menu/add-menu'] = 'menu/edit_menu';
$route['menu/edit-menu/(:any)'] = 'menu/edit_menu/$1';
$route['menu/delete-menu/(:any)'] = 'menu/delete_menu/$1';
$route['menu/save-menu'] = 'menu/save_menu';

$route['access/save-access'] = 'access/save_access';
$route['access/edit-access/(:any)'] = 'access/edit_access/$1';

$route['cs/laporan-kurangan-picker'] = 'cs/laporan_kurangan_picker';
$route['cs/get-laporan-kurangan-picker-data'] = 'cs/get_laporan_kurangan_picker_data';
$route['cs/export-excel-laporan-kurangan-picker'] = 'cs/export_excel_laporan_kurangan_picker';
$route['cs/retur-complain'] = 'cs/retur_complain';
$route['cs/get-retur-complain-data'] = 'cs/get_retur_complain_data';
$route['cs/export-excel-retur-complain'] = 'cs/export_excel_retur_complain';
$route['cs/submit-kurangan-picker'] = 'cs/submit_kurangan_picker';
$route['cs/masalah-picker'] = 'cs/masalah_picker';
$route['cs/get-masalah-picker-data'] = 'cs/get_masalah_picker_data';
$route['cs/get-detail-masalah-picker'] = 'cs/get_detail_masalah_picker';
$route['cs/get-kurangan-preview-data'] = 'cs/get_kurangan_preview_data';
$route['cs/get-user-list-kurangan'] = 'cs/get_user_list_kurangan';
$route['cs/save-proses-kurangan'] = 'cs/save_proses_kurangan';
$route['cs/action-kurangan-picker'] = 'cs/action_kurangan_picker';

$route['cs/complain-management'] = 'cs/complain_management';
$route['cs/complain-management/get-data'] = 'cs/get_complain_management_data';
$route['cs/complain-management/detail/(:num)'] = 'cs/get_complain_management_detail/$1';
$route['cs/complain-management/save'] = 'cs/save_complain_management';
$route['cs/complain-management/delete/(:num)'] = 'cs/delete_complain_management/$1';
$route['cs/complain-management/search-resi'] = 'cs/search_resi_for_complain';
$route['cs/complain-management/get-sku-suggestions'] = 'cs/get_sku_suggestions_with_hpp';
$route['cs/complain-management/kandidat-retur'] = 'cs/get_kandidat_retur_data';
$route['cs/complain-management/tarik-kandidat'] = 'cs/tarik_kandidat_retur';
$route['cs/complain-management/upload-lampiran'] = 'cs/upload_lampiran_complain';
$route['cs/complain-management/delete-lampiran/(:num)'] = 'cs/delete_lampiran_complain/$1';


$route['monitoring/kurangan-picker'] = 'monitoring/kurangan_picker';
$route['monitoring/get-kurangan-picker-data'] = 'monitoring/get_kurangan_picker_data';

$route['restock/laporan-masalah-picker'] = 'restock/laporan_masalah_picker';
$route['restock/get-laporan-masalah-picker-data'] = 'restock/get_laporan_masalah_picker_data';
$route['restock/get-detail-masalah-picker'] = 'restock/get_detail_masalah_picker';
$route['restock/export-laporan-masalah-picker'] = 'restock/export_laporan_masalah_picker';
$route['restock/hapus-masalah-picker'] = 'restock/hapus_masalah_picker';
$route['restock/kembalikan-masalah-picker'] = 'restock/kembalikan_masalah_picker';

$route['shopee_penalty'] = 'shopee_penalty/index';
$route['shopee-penalty'] = 'shopee_penalty/index';
$route['shopee_penalty/input-admin'] = 'shopee_penalty/input_admin';
$route['shopee-penalty/input-admin'] = 'shopee_penalty/input_admin';
$route['shopee_penalty/report'] = 'shopee_penalty/report';
$route['shopee-penalty/report'] = 'shopee_penalty/report';
$route['shopee_penalty/save-penalty'] = 'shopee_penalty/save_penalty';
$route['shopee-penalty/save-penalty'] = 'shopee_penalty/save_penalty';

$route['lost-scan-packer/input'] = 'lost_scan_packer/input';
$route['lost-scan-packer/report'] = 'lost_scan_packer/report';
$route['lost-scan-packer/save'] = 'lost_scan_packer/save';
$route['lost-scan-packer/get-lost-scan-data'] = 'lost_scan_packer/get_lost_scan_data';
$route['lost-scan-packer/delete'] = 'lost_scan_packer/delete';
$route['lost-scan-packer/export-excel'] = 'lost_scan_packer/export_excel';
$route['lost_scan_packer/input'] = 'lost_scan_packer/input';
$route['lost_scan_packer/report'] = 'lost_scan_packer/report';

$route['qc-return'] = 'qc_return/index';
$route['qc-return/add'] = 'qc_return/add';
$route['qc-return/save'] = 'qc_return/save';
$route['qc-return/approval'] = 'qc_return/approval';
$route['qc-return/get-data'] = 'qc_return/get_data';
$route['qc-return/get-data-approval'] = 'qc_return/get_data_approval';
$route['qc-return/do-approve'] = 'qc_return/do_approve';
$route['qc-return/do-approve-all'] = 'qc_return/do_approve_all';
$route['qc-return/get-sku-suggestions'] = 'qc_return/get_sku_suggestions';
$route['qc-return/get-sku-detail'] = 'qc_return/get_sku_detail';
$route['qc-return/export-to-excel'] = 'qc_return/export_to_excel';
$route['qc_return'] = 'qc_return/index';
$route['qc_return/add'] = 'qc_return/add';
$route['qc_return/approval'] = 'qc_return/approval';

// Missing report routes
$route['restock/laporan-reject-display'] = 'restock/laporan_reject_display';
$route['restock/get-laporan-reject-display-data'] = 'restock/get_laporan_reject_display_data';
$route['restock/export-laporan-reject-display'] = 'restock/export_laporan_reject_display';
$route['report/qc-return-report'] = 'qc_return/index';
$route['report/proses-barang-qc'] = 'Report/proses_barang_qc';
$route['report/get_proses_barang_qc_data'] = 'Report/get_proses_barang_qc_data';

// TIM PURCHASING
$route['purchasing/pengembalian-qc']        = 'purchasing/pengembalian_qc';
$route['purchasing/reject']                 = 'purchasing/reject';
$route['purchasing/repair']                 = 'purchasing/repair';
$route['purchasing/laporan-reject']         = 'purchasing/laporan_reject';
$route['purchasing/laporan-repair']         = 'purchasing/laporan_repair';
$route['purchasing/update_proses_perbaiki'] = 'purchasing/update_proses_perbaiki';
$route['purchasing/update_status_repair']   = 'purchasing/update_status_repair';
$route['purchasing/update-no-penyesuaian']  = 'purchasing/update_no_penyesuaian';
$route['purchasing/bulk_no_penyesuaian']    = 'purchasing/bulk_no_penyesuaian';
$route['purchasing/mark-notif-read']        = 'purchasing/mark_notif_read';
$route['purchasing/mark-all-notif-read']    = 'purchasing/mark_all_notif_read';
$route['purchasing/process-bulk-qc']        = 'purchasing/process_bulk_qc';
$route['purchasing/giveaway']               = 'purchasing/giveaway';
$route['purchasing/laporan-giveaway']       = 'purchasing/laporan_giveaway';
$route['purchasing/laporan-penolakan']      = 'purchasing/laporan_penolakan';
$route['purchasing/laporan-tidak-ada']      = 'purchasing/laporan_tidak_ada';
$route['purchasing/kirim-gudang-purchasing'] = 'purchasing/kirim_gudang_purchasing';
$route['purchasing/refresh-menu']           = 'purchasing/refresh_menu';


// TIM ACCOUNTING
$route['accounting/returan-buka']                           = 'accounting/returan_buka';
$route['accounting/form-unggah-surat-jalan']                = 'accounting/form_surat_jalan';
$route['accounting/save-surat-jalan']                       = 'accounting/save_surat_jalan';
$route['accounting/riwayat-surat-jalan']                    = 'accounting/riwayat_surat_jalan';
$route['accounting/delete-surat-jalan/(:num)']              = 'accounting/delete_surat_jalan/$1';
$route['accounting/update-surat-jalan']                     = 'accounting/update_surat_jalan';
$route['accounting/laporan-kurangan-picker']                = 'accounting/laporan_kurangan_picker';
$route['accounting/get-laporan-kurangan-picker-data']       = 'accounting/get_laporan_kurangan_picker_data';
$route['accounting/export-excel-laporan-kurangan-picker']   = 'accounting/export_excel_laporan_kurangan_picker';
$route['accounting/submit-kurangan-picker']                 = 'accounting/submit_kurangan_picker';
$route['accounting/riwayat-surat-jalan-tp']                 = 'accounting/riwayat_surat_jalan_tp';
$route['accounting/update-surat-jalan-tp']                  = 'accounting/update_surat_jalan_tp';
$route['accounting/delete-surat-jalan-tp/(:num)']           = 'accounting/delete_surat_jalan_tp/$1';
$route['accounting/pergantian-barang']                      = 'accounting/pergantian_barang';
$route['accounting/bulk_no_penyesuaian']                    = 'accounting/bulk_no_penyesuaian';
$route['accounting/get-surat-jalan-items']                   = 'accounting/get_surat_jalan_items';
$route['accounting/save-surat-jalan-item']                    = 'accounting/save_surat_jalan_item';
$route['accounting/delete-surat-jalan-item']                 = 'accounting/delete_surat_jalan_item';
$route['accounting/create-surat-jalan-doc']                  = 'accounting/create_surat_jalan_doc';
$route['accounting/upload-bundle-persediaan']                = 'accounting/upload_bundle_persediaan';
$route['accounting/get-surat-jalan-doc-items/(:num)']        = 'accounting/get_surat_jalan_doc_items/$1';
$route['accounting/list-surat-jalan-docs']                   = 'accounting/list_surat_jalan_docs';
$route['accounting/surat-jalan-print/(:num)']                = 'accounting/surat_jalan_print/$1';
$route['accounting/kirim-surat-jalan-inbound']               = 'accounting/kirim_surat_jalan_inbound';
$route['accounting/update-surat-jalan-notrf']                = 'accounting/update_surat_jalan_notrf';
$route['accounting/daftar-surat-jalan']                      = 'accounting/daftar_surat_jalan';
$route['accounting/get-surat-jalan-docs-dt']                 = 'accounting/get_surat_jalan_docs_dt';
$route['accounting/get-sj-worksheet']                        = 'accounting/get_sj_worksheet';
$route['accounting/save-sj-item-cell']                       = 'accounting/save_sj_item_cell';
$route['accounting/upload-transfer-jubelio']                 = 'accounting/upload_transfer_jubelio';
$route['accounting/revert-sj-item-field']                    = 'accounting/revert_sj_item_field';
$route['accounting/get-sj-item-history']                     = 'accounting/get_sj_item_history';
$route['accounting/get-sku-autocomplete']                    = 'accounting/get_sku_autocomplete';
$route['accounting/nomor-rak']                               = 'accounting/nomor_rak';
$route['accounting/get-nomor-rak-dt']                         = 'accounting/get_nomor_rak_dt';
$route['accounting/upload-rak-gudang']                        = 'accounting/upload_rak_gudang';
$route['accounting/download-template-rak-gudang']             = 'accounting/download_template_rak_gudang';
$route['accounting/get-rak-history']                          = 'accounting/get_rak_history';
$route['accounting/update-sku-inline']                        = 'accounting/update_sku_inline';


// INBOUND
$route['inbound/laporan-surat-jalan-tp']        = 'inbound/laporan_surat_jalan_tp';
$route['inbound/update-status-surat-jalan-tp']  = 'inbound/update_status_surat_jalan_tp';
$route['inbound/riwayat-surat-jalan']           = 'inbound/riwayat_surat_jalan';
$route['inbound/update-status-surat-jalan']     = 'inbound/update_status_surat_jalan';
$route['inbound/mark-notif-read']               = 'inbound/mark_notif_read';
$route['inbound/mark-all-notif-read']           = 'inbound/mark_all_notif_read';


// FINANCE
$route['finance/pergantian-barang'] = 'finance/pergantian_barang';
$route['finance/denda']             = 'finance/denda';
$route['finance/bulk_status']       = 'finance/bulk_status';
$route['finance/bulk_acc']          = 'finance/bulk_acc';

$route['ngrok_control'] = 'ngrok_control';
$route['ngrok_control/get_status'] = 'ngrok_control/get_status';
$route['ngrok_control/save_token'] = 'ngrok_control/save_token';


// LAPORAN OPERASIONAL
$route['laporan/totalan-picker']            = 'laporan/totalan_picker';
$route['laporan/totalan-packer']            = 'laporan/totalan_packer';
$route['laporan/sisa-resi']                 = 'laporan/sisa_resi';
$route['laporan/paket-keluar']              = 'laporan/paket_keluar';
$route['laporan/rekap-pencapaian']          = 'laporan/rekap_pencapaian';
$route['laporan/rekap-target']              = 'laporan/rekap_target';
$route['laporan/kelola-target']             = 'laporan/kelola_target';
$route['laporan/get-data-totalan-picker']   = 'laporan/get_data_totalan_picker';
$route['laporan/get-data-totalan-packer']   = 'laporan/get_data_totalan_packer';
$route['laporan/get-data-rekap-picker']     = 'laporan/get_data_rekap_picker';
$route['laporan/get-data-rekap-packer']     = 'laporan/get_data_rekap_packer';
$route['laporan/get-data-rekap-paket-keluar'] = 'laporan/get_data_rekap_paket_keluar';
$route['laporan/export-rekap-picker']       = 'laporan/export_rekap_picker';
$route['laporan/export-rekap-packer']       = 'laporan/export_rekap_packer';
$route['laporan/export-rekap-paket-keluar'] = 'laporan/export_rekap_paket_keluar';
$route['laporan/save-target']               = 'laporan/save_target';
$route['laporan/delete-target']             = 'laporan/delete_target';
$route['laporan/send-wa-sisa-resi']         = 'laporan/send_wa_sisa_resi';
$route['laporan/send-wa-paket-keluar']      = 'laporan/send_wa_paket_keluar';
$route['laporan/wa-status']                 = 'laporan/wa_status';
$route['laporan/ekspedisi-urgent']          = 'laporan/ekspedisi_urgent';
$route['laporan/get-data-ekspedisi-urgent-detail'] = 'laporan/get_data_ekspedisi_urgent_detail';
$route['laporan/send-wa-ekspedisi-urgent']  = 'laporan/send_wa_ekspedisi_urgent';
$route['laporan/tracking-picker']           = 'laporan/tracking_picker';
$route['laporan/get-data-tracking-picker']  = 'laporan/get_data_tracking_picker';
$route['laporan/get-data-tracking-picker-detail'] = 'laporan/get_data_tracking_picker_detail';
$route['laporan/export-tracking-picker']    = 'laporan/export_tracking_picker';
