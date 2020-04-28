<?php
/**
 * Plugin Name: MS Whatsapp Notif
 * Version: 1.0.0
 * Description: Kirim notifikasi WA ke tim packing | Support: tom.wpdev@gmail.com | Phone: 08113644664 
 */

if ( !function_exists( 'add_action' ) ) {
	echo 'Die';
	exit;
}

/**
 * Cek Woo
 */
add_action( 'plugins_loaded', 'require_woocommerce' );
function require_woocommerce() {
	if ( !defined('WC_VERSION') ) { ?>
	<div class="notice notice-error is-dismissible">
        <p><?php _e( 'Anda belum menginstall atau mengaktifkan WooCommerce!', 'sample-text-domain' ); ?></p>
    </div>
	<?php } else {
		/**
		 * Add new column
		 */
		add_filter( 'manage_edit-shop_order_columns', 'wc_new_order_column' );
		function wc_new_order_column( $columns ) {
			unset( $columns['wc_actions'] );
		    $columns['packing_notif'] = 'Beritahu Tim Packing';
		    $columns['wc_actions'] = __( 'Action', 'woocommerce' );
		    return $columns;
		}

		/**
		 * Hmm
		 */
		add_filter( 'manage_shop_order_posts_custom_column', 'wc_new_action_column', 10, 2 );
		function wc_new_action_column( $sip, $lah ) {
			if ( $sip == 'packing_notif' ) {
				$pesanan = new WC_Order( $lah );
				$url = add_query_arg( 'order_id', $lah, get_edit_post_link( $lah ) );
				?>
				<?php if ( $pesanan->get_status() == 'processing' ) : ?>
					<a href="<?php echo $url; ?>" class="kabarin-tim button button-secondary button-small"><span class="dashicons dashicons-megaphone"></span></a>
				<?php endif; ?>
				<?php
			}
		}

		add_filter( 'woocommerce_admin_order_preview_line_item_columns', 'wanotif_dikolom_detail_col', 10, 2 );
		function wanotif_dikolom_detail_col( $columns, $order ) {
			$columns['wanotif'] = __( 'Beritahu Tim Packing' );
			return $columns;
		}

		add_filter( 'woocommerce_admin_order_preview_actions', 'wanotif_dikolom_detail', 10, 2 );
		function wanotif_dikolom_detail( $actions, $order ) { 
			if ( $order->has_status( 'processing' ) ) {
				$actions['status']['actions']['notif'] = array(
					'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=kabarintim&status=processing&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
					'name'   => __( 'Beritahu tim packing', 'woocommerce' ),
					'title'  => __( 'Beritahu tim packing', 'woocommerce' ),
					'action' => 'kabarin-tim',
				);
			}
			return $actions;
		}

		/**
		 * Pasang style
		 */
		add_action( 'admin_head', 'pasang_style_di_halaman_order' );
		function pasang_style_di_halaman_order() {
			global $current_screen, $hook;
			if ( !in_array( $current_screen->id, array( 'edit-shop_order', 'shop_order' ) ) ) return;
			echo '<style type="text/css" media="screen">
			div[ms_timpacking_pl_modal] {
				visibility: hidden;
				opacity: 0;
				transition: .25s ease all;
				position: fixed;
				z-index: 999999;
				width: 400px;
				background-color: #fff;
				top: 50%;
				left: 50%;
				box-shadow: 0 10px 30px rgba(0,0,0,.3);
				border-radius: 8px;
				transform: translate( -50%,-50% );
			}
			div[ms_timpacking_pl_modal] .button {
				text-align: center;
			}
			div[ms_timpacking_pl_modal].open {
				visibility: visible;
				opacity: 1;
			}
			div[ms_timpacking_pl_modal_close] {
				text-align: right;
			}
			div[ms_timpacking_pl_modal_close] a {
				display: inline-block;
				color: red;
				margin: 10px 10px 0 0;
				font-size: 20px;
				cursor: pointer;
			}
			div[ms_timpacking_pl_modal_body] {
				padding: 0 30px 20px;
			}
			div[ms_timpacking_pl_modal_body] textarea {
				min-height: 100px;
			}
			body.ms_timpacking_pl_modal {
				position: relative;
				height: 100%;
				overflow: hidden;
			}
			body.ms_timpacking_pl_modal:before {
				content: "";
				display: block;
				position: fixed;
				z-index: 991999;
				top: 0;
				right: 0;
				left: 0;
				bottom: 0;
				background-color: rgba(0,0,0,.7)
			}
			</style>';
		}

		/**
		 * Pasang sekrip di halaman order list
		 */
		add_action( 'admin_footer', 'pasang_script_di_halaman_order' );
		function pasang_script_di_halaman_order() {
			global $current_screen, $hook;
			if ( !in_array( $current_screen->id, array( 'edit-shop_order', 'shop_order' ) ) ) return;
			?>
			<div ms_timpacking_pl_modal>
				<div ms_timpacking_pl_modal_close>
					<a>&times;</a>
				</div>
				<div ms_timpacking_pl_modal_body>
					<h3 ms_timpacking_pl_modal_title></h3>
					<p>
						<b><label>Penanggung Jawab</label></b>
						<select ms_timpacking_pl_penanggung_jawab class="widefat">
							<option value="">--Pilih Penanggung Jawab--</option>
							<?php if ( '' <> get_option( 'mswanotif_daftar_tim' ) ) :
								$tim = get_option( 'mswanotif_daftar_tim' );
								$tim = explode( PHP_EOL, $tim );
							?>
								<?php $i=0; foreach( $tim as $orang ) : 
									$n = explode('|', $orang);
									$n = array_map('trim', $n);
									?>
									<option <?php if( $i==0) { echo 'selected="selected"'; }; ?> value="<?php echo $orang; ?>"><?php echo $n[0]; ?> <?php echo $n[1]; ?></option>
								<?php $i++; endforeach; ?> 
							<?php endif; ?>
							<option value="new">Nomor Lainnya</option>
						</select>
					</p>
					<p id="nomor-lain" style="display: none;">
						<input type="text" class="widefat" placeholder="62811xxxxxx" ms_timpacking_nomor_lain>
					</p>
					<p>
						<b><label>Catatan</label></b>
						<textarea placeholder="Mohon di packing yang rapi ya" ms_timpacking_pl_catatan class="widefat">
Mohon segera diproses!

{{produk}}</textarea>
						<small>Bisa pakai kode :<br/>
							{{produk}}<br/>
							{{id_pesanan}}<br/>
							{{nama_pesanan}}<br/>
							{{link_pesanan}}<br/>
							{{nama_pembeli}}<br/>
							{{nama_penerima}}
						</small>
					</p>
					<p><a ms_timpacking_submit class="button button-primary button-large widefat"><?php _e( 'Beritahu Tim', 'ms-printlabel' ); ?></a></p>
				</div>
			</div>
			<script type="text/javascript">
				function getParameterByName(name, url) {
					if (!url) url = window.location.href;
					name = name.replace(/[\[\]]/g, '\\$&');
					var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
					results = regex.exec(url);
					if (!results) return null;
					if (!results[2]) return '';
					return decodeURIComponent(results[2].replace(/\+/g, ' '));
				}

				(function($){
					var order_id;
					$('body').on('click','a.kabarin-tim',function(e){
						e.preventDefault();
						// $('[ms_timpacking_pl_catatan],[ms_timpacking_pl_label_service],[ms_timpacking_pl_penanggung_jawab]').val('');
						var thisUrl = $(this).attr('href');
						order_id = getParameterByName('order_id', thisUrl);
						$('body').addClass('ms_timpacking_pl_modal');
						$('div[ms_timpacking_pl_modal]').addClass('open');
						$('div[ms_timpacking_pl_modal_body] [ms_timpacking_pl_modal_title]').html('Beritahu tim packing #' + order_id);
						$('div[ms_timpacking_pl_modal_body] [ms_timpacking_submit]').attr('href', thisUrl);
						console.log(e);
					});
					$('[ms_timpacking_pl_modal_close] a').on('click',function(){
						$('body').removeClass('ms_timpacking_pl_modal');
						$('div[ms_timpacking_pl_modal]').removeClass('open');
					});
					$('[ms_timpacking_pl_penanggung_jawab]').on('change',function() {
						$('[ ms_timpacking_nomor_lain]').val('');
						if ( $(this).val() == 'new' ) {
							$('#nomor-lain').show();
						} else {
							$('#nomor-lain').hide();
						}
					})
					$('a[ms_timpacking_submit]').on('click',function(e){
						e.preventDefault();
						var $this = $(this);
						var thisText = $(this).text();
						var thisUrl = $(this).attr('href');
						var notes = $('[ms_timpacking_pl_catatan]').val();
						var kurir = $('[ms_timpacking_pl_penanggung_jawab]').val();

						if ( '' == kurir ) {
							alert( 'Anda belum memilih penanggung jawab' );
							return false;
						}

						if ( kurir == 'new' ) {
							if ( $('[ms_timpacking_nomor_lain]').val() == '' ) {
								alert( 'Anda belum memasukkan nomor penanggung jawab' );
								return false;
							} else {
								kurir = $('[ms_timpacking_nomor_lain]').val();	
							}
						}

						$(this).text('Sedang memproses...');

						$.ajax({
							url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
							type: "POST",
							data: {
								action: 'kabarin_penanggung_jawab',
								order_id: order_id,
								catatan: notes,
								penanggung_jawab: kurir
							}
						}).done(function(e){
							$this.text(thisText);
							if ( e.url ) {
								window.open(e.url, "_blank");
							} else {
								alert( 'Gagal kasih tau' );
							}
						})
					});
				})(jQuery);
			</script>
			<?php
		}

		add_action( 'wp_ajax_kabarin_penanggung_jawab', 'kabarin_penanggung_jawab' );
		add_action( 'wp_ajax_nopriv_kabarin_penanggung_jawab', 'kabarin_penanggung_jawab' );
		function kabarin_penanggung_jawab() {
			$catatan 	= $_REQUEST['catatan'];
			$produk 	= '';
			$pesanan 	= wc_get_order( $_REQUEST['order_id'] );
			$waurl		= 'https://web.whatsapp.com/send?';
			if ( wp_is_mobile() ) {
				$waurl = 'https://api.whatsapp.com/send?';
			}

			if ( false !== strpos( $_REQUEST['penanggung_jawab'], '|' ) ) {
				$phone = explode('|', $_REQUEST['penanggung_jawab'] );
				$phone = trim( $phone[1] );
			} else {
				$phone = $_REQUEST['penanggung_jawab'];
			}

			$i = 1;
			foreach( $pesanan->get_items() as $id_pesanan => $item ) {
				$data_produk = $item->get_product();
				$produk .= sprintf( "_Produk_: *%s*\r\n_SKU_: *%s*\r\n_Jumlah_: *%s*\r\n\r\n", $item->get_name(), ($data_produk->get_sku() != '' ? $data_produk->get_sku() : '-'), $item->get_quantity() );
				if ( count( $pesanan->get_items() ) != $i ) {
					$produk .= "----\r\n\r\n";
				}
			$i++; }

			$catatan = str_replace( '{{produk}}', $produk, $catatan );
			$catatan = str_replace( '{{id_pesanan}}', sprintf('*#%s*',$pesanan->get_id()), $catatan );
			$catatan = str_replace( '{{nama_pesanan}}', sprintf('*Order #%s*', $pesanan->get_id()), $catatan );
			$catatan = str_replace( '{{link_pesanan}}', get_edit_post_link( $pesanan->get_id() ), $catatan );
			$catatan = str_replace( '{{nama_pembeli}}', $pesanan->get_formatted_billing_full_name(), $catatan );
			$catatan = str_replace( '{{nama_penerima}}', $pesanan->get_formatted_shipping_full_name(), $catatan );
			
			$wa_message_url = add_query_arg( array(
				'phone' => $phone,
				'text'	=> urlencode( $catatan )
			), $waurl );

			wp_send_json( array( 'url' => $wa_message_url ) );
			exit;
		}
		/**
		 * Menu pengaturan
		 */
		add_action( 'admin_menu', 'halaman_pengaturan_wanotif' );
		function halaman_pengaturan_wanotif() {
			add_submenu_page( 'woocommerce', 'Pengaturan Tim Packing', 'Tim Packing', 'administrator', 'pengaturan_tim_packing', 'halaman_pengaturan_timpacking' );
		}

		/**
		 * Pengaturan
		 */
		function halaman_pengaturan_timpacking() {
			if ( isset( $_POST['mswanotif_action'] ) ) {
				if ( isset( $_POST['daftar_tim'] ) ) {
					update_option( 'mswanotif_daftar_tim', $_POST['daftar_tim'] );
				}
			}
			?>
			<div class="wrap">
				<h2>Daftar Tim Packing</h2>
				<div class="card">
					<form method="post">
						<p>
							<textarea class="widefat" name="daftar_tim" rows="4"><?php echo get_option( 'mswanotif_daftar_tim' ); ?></textarea>
							<small>Simpan daftar tim dengan format: Nama|Nomor, satu baris per orang. Contoh:</small><br/>
							<small><b>Ahmad|628113644664</b></small><br/>
							<small><b>Royyan|628113644664</b></small><br/>
							<small>Wajib menggunakan kode negara tanpa tanda +</small>
						</p>
						<input type="hidden" name="mswanotif_action" value="simpan_pengaturan">
						<?php submit_button( 'Simpan', 'primary', 'submit', false ); ?>
					</form>
				</div>
			</div>
			<?php
		}

		add_action( 'add_meta_boxes', 'ms_tambah_metabox_diorder' );
		if ( ! function_exists( 'ms_tambah_metabox_diorder' ) )
		{
		    function ms_tambah_metabox_diorder()
		    {
		        add_meta_box( 'ordermetabox_tambahan', __('Beritahu Tim Packing','woocommerce'), 'ms_order_kotak_metabox', 'shop_order', 'side', 'core' );
		    }
		}

		// Adding Meta field in the meta container admin shop_order pages
		if ( ! function_exists( 'ms_order_kotak_metabox' ) )
		{
		    function ms_order_kotak_metabox()
		    {
		    	$pesanan = new WC_Order( $_GET['post'] );
				$url = add_query_arg( 'order_id', $_GET['post'], get_edit_post_link( $_GET['post'] ) );
				?>
				<?php if ( $pesanan->get_status() == 'processing' ) : ?>
					<p><a style="text-align: center" href="<?php echo $url; ?>" class="kabarin-tim button button-primary widefat"><span class="dashicons dashicons-megaphone"></span> Beritahu Tim Packing</a></p>
				<?php endif; ?>
				<?php

		    }
		}
	}
}