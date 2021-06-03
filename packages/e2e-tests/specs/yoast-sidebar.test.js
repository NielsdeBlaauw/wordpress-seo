/**
 * WordPress e2e utilities
 */
import {
	createNewPost,
	trashAllPosts,
} from "@wordpress/e2e-test-utils";

describe( "Yoast SEO sidebar", () => {

	it( "shows correctly Yoast SEO sidebar when editing a post", async () => {
		await trashAllPosts();
		await createNewPost();

		await page.waitForSelector( "aria/Yoast SEO" );
		const yoastSeoSidebarButton = await page.$x(
			`//button[contains( @aria-label, "Yoast SEO" )]`
		);
		expect( yoastSeoSidebarButton ).not.toBe( 1 );
		
		await page.click( "aria/Yoast SEO" );

		expect(
			`//div[contains( @class, interface-complementary-area-header )][contains ( text(), "Yoast SEO )]`
		).not.toBeNull();

		expect(
			`//label[contains( @for, "focus-keyword-input-sidebar" )][contains( text(), "Focus keyphrase" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Readability analysis" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "SEO analysis" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Add related keyphrase" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Google preview" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Facebook preview" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Twitter preview" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Schema" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Advanced" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Cornerstone content" )]`
		).not.toBeNull();
	} );

	it( "shows correctly Yoast SEO sidebar when editing a page", async () => {
		await trashAllPosts( "page" );
		await createNewPost( "page" );

		await page.waitForSelector( "aria/Yoast SEO" );
		const yoastSeoSidebarButton = await page.$x(
			`//button[contains( @aria-label, "Yoast SEO" )]`
		);
		expect( yoastSeoSidebarButton ).not.toBe( 1 );
		
		await page.click( "aria/Yoast SEO" );

		expect(
			`//div[contains( @class, interface-complementary-area-header )][contains ( text(), "Yoast SEO )]`
		).not.toBeNull();

		expect(
			`//label[contains( @for, "focus-keyword-input-sidebar" )][contains( text(), "Focus keyphrase" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Readability analysis" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "SEO analysis" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Add related keyphrase" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Google preview" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Facebook preview" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Twitter preview" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Schema" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Advanced" )]`
		).not.toBeNull();

		expect(
			`//div[contains( text(), "Cornerstone content" )]`
		).not.toBeNull();
	} );

} );