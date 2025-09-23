from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Login
    page.goto("https://fossbilling.dev.ddev.site/login")
    page.get_by_label("Email address").fill("client@fossbilling.org")
    page.get_by_label("Password").fill("client")
    page.get_by_role("button", name="Log in").click()

    # Wait for navigation to complete
    page.wait_for_url("https://fossbilling.dev.ddev.site/")

    # Navigate to service management page
    page.goto("https://fossbilling.dev.ddev.site/order/service/manage/1")

    # Click on the Hestia Management tab
    hestia_tab = page.get_by_role("link", name="Hestia Management")
    expect(hestia_tab).to_be_visible()
    hestia_tab.click()

    # Wait for the domains list to be loaded
    expect(page.locator("#hestia-domains-list")).to_contain_text("No domains found", timeout=10000)

    # Take a screenshot
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
