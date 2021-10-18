/* global wpseoAdminGlobalL10n */

/* External dependencies */
import PropTypes from "prop-types";
import { Fragment, Component } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";
import { isEmpty, filter } from "lodash-es";
import styled from "styled-components";

/* Yoast dependencies */
import { getDirectionalStyle, makeOutboundLink } from "@yoast/helpers";

/* Internal dependencies */
import WincherTableRow from "./WincherTableRow";
import {
	getAccountLimits,
	getKeyphrases,
	getKeyphrasesChartData,
	handleAPIResponse,
	trackKeyphrases,
	untrackKeyphrase,
} from "../helpers/wincherEndpoints";

const GetMoreInsightsLink = makeOutboundLink();

const FocusKeyphraseFootnote = styled.span`
	position: absolute;
	${ getDirectionalStyle( "right", "left" ) }: 8px;
	font-style: italic;
`;

/**
 * The WincherKeyphrasesTable component.
 */
class WincherKeyphrasesTable extends Component {
	/**
	 * Constructs the Related Keyphrases table.
	 *
	 * @param {Object} props The props for the Related Keyphrases table.
	 *
	 * @returns {void}
	 */
	constructor( props ) {
		super( props );

		this.onTrackKeyphrase     = this.onTrackKeyphrase.bind( this );
		this.onUntrackKeyphrase   = this.onUntrackKeyphrase.bind( this );
		this.getTrackedKeyphrases = this.getTrackedKeyphrases.bind( this );

		this.interval = null;
	}

	/**
	 * Performs the tracking request for one or more keyphrases.
	 *
	 * @param {Array|string} keyphrases The keyphrase(s) to track.
	 *
	 * @returns {void}
	 */
	async performTrackingRequest( keyphrases ) {
		const {
			setRequestLimitReached,
			addTrackingKeyphrase,
			setRequestSucceeded,
			setRequestFailed,
		} = this.props;

		const trackLimits = await getAccountLimits();

		if ( ! trackLimits.canTrack ) {
			setRequestLimitReached( trackLimits.limit );

			return;
		}

		await handleAPIResponse(
			() => trackKeyphrases( keyphrases ),
			async( response ) => {
				setRequestSucceeded( response );
				addTrackingKeyphrase( response.results );
			},
			async( response ) => {
				setRequestFailed( response );
			},
			201
		);
	}

	/**
	 * Fires when a keyphrase is set to be tracked.
	 *
	 * @param {string} keyphrase The keyphrase to track.
	 *
	 * @returns {void}
	 */
	async onTrackKeyphrase( keyphrase ) {
		const { newRequest } = this.props;

		// Prepare a new request.
		newRequest( keyphrase );

		await this.performTrackingRequest( keyphrase );
		await this.getTrackedKeyphrasesChartData( Object.keys( this.props.trackedKeyphrases ) );
	}

	/**
	 * Fires when a keyphrase is set to be untracked.
	 *
	 * @param {string} keyphrase The keyphrase to untrack.
	 * @param {string} keyphraseID The keyphrase ID to untrack.
	 *
	 * @returns {void}
	 */
	async onUntrackKeyphrase( keyphrase, keyphraseID ) {
		const {
			setRequestSucceeded,
			removeTrackingKeyphrase,
			setRequestFailed,
		} = this.props;

		await handleAPIResponse(
			() => untrackKeyphrase( keyphraseID ),
			( response ) => {
				setRequestSucceeded( response );
				removeTrackingKeyphrase( keyphrase.toLowerCase() );
			},
			async( response ) => {
				setRequestFailed( response );
			},
			204
		);
	}

	/**
	 * Gets the tracked keyphrases.
	 *
	 * @param {Array} keyphrases The keyphrases used in the post.
	 *
	 * @returns {void}
	 */
	 async getTrackedKeyphrases( keyphrases ) {
		const {
			setRequestSucceeded,
			setTrackingKeyphrases,
			setRequestFailed,
		} = this.props;

		await handleAPIResponse(
			() => getKeyphrases( keyphrases ),
			async( response ) => {
				setRequestSucceeded( response );
				setTrackingKeyphrases( response.results );

				if ( isEmpty( response.results ) ) {
					return;
				}

				// Get the chart data.
				await this.getTrackedKeyphrasesChartData( keyphrases );
			},
			async( response ) => {
				setRequestFailed( response );
			}
		);
	}

	/**
	 * Gets the chart data for the tracked keyphrases.
	 *
	 * @param {Array} keyphrases The keyphrases used in the post.
	 *
	 * @returns {void}
	 */
	async getTrackedKeyphrasesChartData( keyphrases = [] ) {
		const {
			setPendingChartRequest,
			setRequestSucceeded,
			setTrackingCharts,
			setRequestFailed,
		} = this.props;

		await handleAPIResponse(
			() => getKeyphrasesChartData( keyphrases, window.wp.data.select( "core/editor" ).getPermalink() ),
			async( response ) => {
				setRequestSucceeded( response );
				setTrackingCharts( response.results );

				const keyphrasesHaveNoRankingData = this.noKeyphrasesHaveRankingData();

				if ( keyphrasesHaveNoRankingData ) {
					setPendingChartRequest( true );
				} else {
					clearInterval( this.interval );
					setPendingChartRequest( false );
				}
			},
			async( response ) => {
				setRequestFailed( response );
			}
		);
	}

	/**
	 * Determines whether the amount of tracked keyphrases is equal to the amount of keyphrases without ranking data.
	 *
	 * @returns {boolean} Whether there are equal amounts of keyphrases and missing ranking data
	 */
	noKeyphrasesHaveRankingData() {
		const { trackedKeyphrases } = this.props;

		const positionalData = filter( trackedKeyphrases, ( trackedKeyphrase ) => {
			return isEmpty( trackedKeyphrase.ranking_updated_at );
		} );

		return positionalData.length === Object.keys( trackedKeyphrases ).length;
	}

	/**
	 * Loads tracking data when the table is mounted in the DOM.
	 *
	 * @returns {void}
	 */
	async componentDidMount() {
		const { trackAll, isLoggedIn, keyphrases } = this.props;

		if ( ! isLoggedIn ) {
			return;
		}

		if ( trackAll ) {
			await this.performTrackingRequest( keyphrases );

			return;
		}

		this.interval = setInterval( async() => {
			await this.getTrackedKeyphrases( keyphrases );
		}, 10000 );

		await this.getTrackedKeyphrases( keyphrases );
	}

	/**
	 * Reset the interval if component changed.
	 *
	 * @returns {void}
	 */
	componentDidUpdate() {
		const {
			keyphrases,
			isLoggedIn,
			isNewlyAuthenticated,
		} = this.props;

		if ( ! isLoggedIn ) {
			return;
		}

		if ( isNewlyAuthenticated ) {
			this.getTrackedKeyphrases( keyphrases );
		}

		if ( this.noKeyphrasesHaveRankingData() ) {
			clearInterval( this.interval );
			this.interval = setInterval( async() => {
				await this.getTrackedKeyphrases( keyphrases );
			}, 10000 );
		}
	}

	/**
	 * Unsets the polling when the modal is closed.
	 *
	 * @returns {void}
	 */
	componentWillUnmount() {
		clearInterval( this.interval );
	}

	/**
	 * Gets the passed keyphrase from the tracked keyphrases data object.
	 *
	 * @param {string} keyphrase The keyphrase to search for.
	 *
	 * @returns {Object|null} The keyphrase object. Returns null if it can't be found.
	 */
	getKeyphraseData( keyphrase ) {
		const { trackedKeyphrases } = this.props;
		const targetKeyphrase = keyphrase.toLowerCase();

		if ( trackedKeyphrases && ! isEmpty( trackedKeyphrases ) && trackedKeyphrases.hasOwnProperty( targetKeyphrase ) ) {
			return trackedKeyphrases[ targetKeyphrase ];
		}

		return null;
	}

	/**
	 * Gets the passed keyphrase from the tracked keyphrases data object.
	 *
	 * @param {string} keyphrase The keyphrase to search for.
	 *
	 * @returns {Object|null} The keyphrase object. Returns null if it can't be found.
	 */
	getKeyphraseChartData( keyphrase ) {
		const { trackedKeyphrasesChartData } = this.props;
		const targetKeyphrase = keyphrase.toLowerCase();

		if ( trackedKeyphrasesChartData && ! isEmpty( trackedKeyphrasesChartData ) && trackedKeyphrasesChartData.hasOwnProperty( targetKeyphrase ) ) {
			return trackedKeyphrasesChartData[ targetKeyphrase ];
		}

		return null;
	}

	/**
	 * Renders the table.
	 *
	 * @returns {React.Element} The table.
	 */
	render() {
		const {
			allowToggling,
			websiteId,
			keyphrases,
			chartDataTs,
			isLoggedIn,
		} = this.props;

		const isDisabled = ! isLoggedIn;

		return (
			keyphrases && ! isEmpty( keyphrases ) && <Fragment>
				<table className="yoast yoast-table">
					<thead>
						<tr>
							{ allowToggling && <th
								scope="col"
								abbr={ __( "Tracking", "wordpress-seo" ) }
							>
								{ __( "Tracking", "wordpress-seo" ) }
							</th> }
							<th
								scope="col"
								abbr={ __( "Keyphrase", "wordpress-seo" ) }
							>
								{ __( "Keyphrase", "wordpress-seo" ) }
							</th>
							<th
								scope="col"
								abbr={ __( "Position", "wordpress-seo" ) }
							>
								{ __( "Position", "wordpress-seo" ) }
							</th>
							<th
								scope="col"
								abbr={ __( "Position over time", "wordpress-seo" ) }
							>
								{ __( "Position over time", "wordpress-seo" ) }
							</th>
							<td className="yoast-table--nobreak" />
						</tr>
					</thead>
					<tbody>
						{
							keyphrases.map( ( keyphrase, index ) => {
								return ( <WincherTableRow
									key={ `trackable-keyphrase-${index}` }
									keyphrase={ keyphrase }
									allowToggling={ allowToggling }
									onTrackKeyphrase={ this.onTrackKeyphrase }
									onUntrackKeyphrase={ this.onUntrackKeyphrase }
									rowData={ this.getKeyphraseData( keyphrase ) }
									chartData={ this.getKeyphraseChartData( keyphrase ) }
									chartDataTs={ chartDataTs }
									isFocusKeyphrase={ index === 0 }
									websiteId={ websiteId }
									isDisabled={ isDisabled }
								/> );
							} )
						}
					</tbody>
				</table>
				<p style={ { marginBottom: 0, position: "relative" } }>
					<GetMoreInsightsLink
						href={ wpseoAdminGlobalL10n[ "links.wincher.login" ] }
					>
						{ sprintf(
							/* translators: %s expands to Wincher */
							__( "Get more insights over at %s", "wordpress-seo" ),
							"Wincher"
						) }
					</GetMoreInsightsLink>
					<FocusKeyphraseFootnote>
						{ __( "* focus keyphrase", "wordpress-seo" ) }
					</FocusKeyphraseFootnote>
				</p>
			</Fragment>
		);
	}
}

WincherKeyphrasesTable.propTypes = {
	keyphrases: PropTypes.array,
	trackedKeyphrases: PropTypes.object,
	trackedKeyphrasesChartData: PropTypes.object,
	allowToggling: PropTypes.bool,
	isLoggedIn: PropTypes.bool,
	trackAll: PropTypes.bool,
	newRequest: PropTypes.func.isRequired,
	setRequestSucceeded: PropTypes.func.isRequired,
	setRequestLimitReached: PropTypes.func.isRequired,
	setRequestFailed: PropTypes.func.isRequired,
	addTrackingKeyphrase: PropTypes.func.isRequired,
	setPendingChartRequest: PropTypes.func.isRequired,
	setTrackingCharts: PropTypes.func.isRequired,
	removeTrackingKeyphrase: PropTypes.func.isRequired,
	setTrackingKeyphrases: PropTypes.func.isRequired,
	websiteId: PropTypes.number,
	chartDataTs: PropTypes.number,
	isNewlyAuthenticated: PropTypes.bool,
};

WincherKeyphrasesTable.defaultProps = {
	keyphrases: [],
	trackedKeyphrases: {},
	trackedKeyphrasesChartData: {},
	allowToggling: true,
	isLoggedIn: false,
	trackAll: false,
	websiteId: 0,
	chartDataTs: 0,
	isNewlyAuthenticated: false,
};

export default WincherKeyphrasesTable;
