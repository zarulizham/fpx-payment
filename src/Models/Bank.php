<?php

namespace JagdishJP\FpxPayment\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{

	public const STATUS_ONLINE = 'Online';
	public const STATUS_OFFLINE = 'Offline';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'bank_id',
		'name',
		'short_name',
		'status',
		'type',
		'position',
	];

	/**
	 * Scope a query to only include B2B Type
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeIsB2B($query)
	{
		return $query->where('type', 'B2B');
		// return $query->whereJsonContains('type', ['B2B']);
	}

	/**
	 * Scope a query to only include B2B Type
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeIsB2C($query)
	{
		return $query->where('type', 'B2C');
	}

	/**
	 * Scope a query to only include B2B Type
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeType($query, $type)
	{
		return $query->where('type', $type);
	}

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = ['created_at', 'updated_at'];

	public function isOnline()
	{
		return $this->status === self::STATUS_ONLINE;
	}

	public function isOffline()
	{
		return $this->status === self::STATUS_OFFLINE;
	}

	public function getNameAttribute()
	{

		return $this->short_name . ($this->isOffline() ? " (offline)" : '');
	}
}
