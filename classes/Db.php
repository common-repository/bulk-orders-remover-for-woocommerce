<?php

/**
 * Created by PhpStorm.
 * User: webgp
 * Date: 18.09.2019
 * Time: 22:35
 */

namespace FPWD\Bulk_Orders_Remover;

/**
 * Class Db
 *
 * @package FPWD\Bulk_Orders_Remover
 */
class Db {
	/** @var string */
	private $orders_tbl = "posts";

	/** @var string */
	private $order_itemmeta_tbl = "woocommerce_order_itemmeta";

	/** @var string */
	private $order_items_tbl = "woocommerce_order_items";

	/** @var string */
	private $order_comments_tbl = "comments";

	/** @var string */
	private $order_comment_meta_tbl = "commentmeta";

	/** @var string */
	private $orders_meta_tbl = "postmeta";

	/**
	 * Db constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->orders_tbl             = $wpdb->prefix . $this->orders_tbl;
		$this->order_itemmeta_tbl     = $wpdb->prefix . $this->order_itemmeta_tbl;
		$this->order_items_tbl        = $wpdb->prefix . $this->order_items_tbl;
		$this->order_comments_tbl     = $wpdb->prefix . $this->order_comments_tbl;
		$this->order_comment_meta_tbl = $wpdb->prefix . $this->order_comment_meta_tbl;
		$this->orders_meta_tbl        = $wpdb->prefix . $this->orders_meta_tbl;
	}

	/**
	 * @param string $treshold_date Count all orders until this date which should be removed
	 */
	public function count_order_to_delete( $treshold_date ) {
		global $wpdb;
		$where = [ 'post_date_gmt' => $wpdb->prepare( "post_date_gmt < %s", $treshold_date ) ];
		$where = apply_filters( 'borfw/trash_orders/query/where', $where );

		$query = "SELECT COUNT(*) FROM " . $this->orders_tbl . " ";
		$query .= "WHERE post_type = 'shop_order' AND ";
		$query .= join( ' AND ', $where );

		$result = $wpdb->query( $query );
		return $result;
	}

	/**
	 * @param string $treshold_date All orders until this date will be removed
	 */
	public function set_trashed_orders( $treshold_date ) {
		global $wpdb;
		$where = [ 'post_date_gmt' => $wpdb->prepare( "post_date_gmt < %s", $treshold_date ) ];
		$where = apply_filters( 'borfw/trash_orders/query/where', $where );

		$query = "UPDATE " . $this->orders_tbl . " SET post_status = 'trash' ";
		$query .= "WHERE post_type = 'shop_order' AND ";
		$query .= join( ' AND ', $where );

		$wpdb->query( $query );
	}

	/**
	 * Remove all orders item meta for trashed orders
	 */
	public function delete_order_item_meta() {
		global $wpdb;

		$query = "DELETE woim FROM " . $this->order_itemmeta_tbl . " as woim ";
		$query .= "JOIN " . $this->order_items_tbl . " as woi ON woim.order_item_id = woi.order_item_id ";
		$query .= "JOIN " . $this->orders_tbl . " as p ON woi.order_id = p.ID ";
		$query .= "WHERE p.post_type='shop_order' AND p.post_status='trash'";

		$wpdb->query( $query );
	}

	/**
	 * Remove all orders items for trashed orders
	 */
	public function delete_order_items() {
		global $wpdb;

		$query = "DELETE woi FROM " . $this->order_items_tbl . " as woi ";
		$query .= "JOIN " . $this->orders_tbl . " as p ON woi.order_id = p.ID ";
		$query .= "WHERE p.post_type='shop_order' AND p.post_status='trash'";

		$wpdb->query( $query );
	}

	/**
	 * Remove all order comment meta
	 */
	public function delete_order_note_meta() {
		global $wpdb;

		$query = "DELETE cm FROM " . $this->order_comment_meta_tbl . " as cm ";
		$query .= "JOIN " . $this->order_comments_tbl . " as c ON cm.comment_id = c.comment_ID ";
		$query .= "JOIN " . $this->orders_tbl . " as p ON c.comment_post_ID = p.ID ";
		$query .= "WHERE p.post_type='shop_order' AND p.post_status='trash'";

		$wpdb->query( $query );
	}

	/**
	 * Remove all orders items for trashed orders
	 */
	public function delete_order_notes() {
		global $wpdb;

		$query = "DELETE c FROM " . $this->order_comments_tbl . " as c ";
		$query .= "JOIN " . $this->orders_tbl . " as p ON c.comment_post_ID = p.ID ";
		$query .= "WHERE p.post_type='shop_order' AND p.post_status='trash'";

		$wpdb->query( $query );
	}

	/**
	 * Remove all orders meta for trashed orders
	 */
	public function delete_order_meta() {
		global $wpdb;

		$query = "DELETE pm FROM " . $this->orders_meta_tbl . " as pm ";
		$query .= "JOIN " . $this->orders_tbl . " as p ON pm.post_id = p.ID ";
		$query .= "WHERE p.post_type='shop_order' AND p.post_status='trash'";

		$wpdb->query( $query );
	}

	/**
	 * Remove all trashed orders
	 */
	public function delete_orders() {
		global $wpdb;

		$query = "DELETE FROM " . $this->orders_tbl . " ";
		$query .= "WHERE post_type='shop_order' AND post_status='trash'";

		$wpdb->query( $query );
	}
}