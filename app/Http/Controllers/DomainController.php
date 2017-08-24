<?php

namespace App\Http\Controllers;

use App\Domain;
use App\Exceptions\DomainExistsException;
use App\Exceptions\DomainLimitReachedException;
use App\Exceptions\InvalidDomainException;
use App\Transformers\DomainTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DomainController extends Controller
{
	public function __construct( DomainTransformer $transformer )
	{
		$this->transformer = $transformer;
		parent::__construct();
	}

	/**
	 * @return void
	 */
	public function destroy( Request $request, $domain )
	{
		$license = $this->getLicense( $request->input( 'license_key' ));
		if( $domain = $license->hasDomain( $domain )) {
			$domain->forceDelete();
		}
		else {
			throw new InvalidDomainException;
		}
	}

	/**
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index( Request $request )
	{
		return $this->respondWithArray([
			'data' => $this->getLicense( $request->input( 'license_key' ))->domains->pluck( 'domain' )->toArray()
		]);
	}

	/**
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store( Request $request )
	{
		$domain = app( Domain::class );
		$license = $this->getLicense( $request->input( 'license_key' ));
		$this->validate( $request, $domain->rules );
		if( $license->hasDomain( $request->input( 'domain' ))) {
			throw new DomainExistsException;
		}
		if( $license->domains()->count() >= $license->max_domains_allowed ) {
			throw new DomainLimitReachedException;
		}
		return $this->respondWithItem( $domain->create([
			'domain' => $request->input( 'domain' ),
			'license_id' => $license->id,
		]), $this->transformer );
	}
}
