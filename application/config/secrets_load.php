<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pemuat kredensial.
 *
 * Nilai asli disimpan di application/config/secrets.php yang TIDAK ikut
 * di-commit (lihat .gitignore). File config biasa (database.php,
 * whatsapp.php, pusher.php) memanggil iresis_secret() untuk membacanya,
 * sehingga tidak ada kredensial yang masuk ke repo.
 *
 * Deploy di mesin baru: salin secrets.php.example menjadi secrets.php
 * lalu isi nilainya.
 */
if ( ! function_exists('iresis_secrets'))
{
	function iresis_secrets()
	{
		static $cache = NULL;

		if ($cache !== NULL)
		{
			return $cache;
		}

		$file = APPPATH.'config/secrets.php';

		if ( ! file_exists($file))
		{
			exit(
				'File application/config/secrets.php belum ada. '
				.'Salin application/config/secrets.php.example menjadi secrets.php, '
				.'lalu isi kredensialnya.'
			);
		}

		$cache = require($file);

		if ( ! is_array($cache))
		{
			exit('application/config/secrets.php harus mengembalikan array.');
		}

		return $cache;
	}
}

if ( ! function_exists('iresis_secret'))
{
	/**
	 * @param string $key     nama kredensial
	 * @param mixed  $default dipakai kalau key tidak ada di secrets.php
	 */
	function iresis_secret($key, $default = NULL)
	{
		$secrets = iresis_secrets();

		if ( ! array_key_exists($key, $secrets))
		{
			if (func_num_args() < 2)
			{
				exit('Kredensial "'.$key.'" belum diisi di application/config/secrets.php.');
			}

			return $default;
		}

		return $secrets[$key];
	}
}
