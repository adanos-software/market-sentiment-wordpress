from pathlib import Path


REPO_ROOT = Path(__file__).resolve().parents[1]
PLUGIN_FILE = REPO_ROOT / "adanos-market-sentiment-widgets.php"
README_FILE = REPO_ROOT / "readme.txt"
ADMIN_CSS_FILE = REPO_ROOT / "assets" / "admin.css"
WIDGETS_FILE = REPO_ROOT / "assets" / "widgets.js"


def test_plugin_uses_distinctive_name_and_text_domain():
    plugin = PLUGIN_FILE.read_text()

    assert "Plugin Name: Adanos Market Sentiment Widgets" in plugin
    assert "Text Domain: adanos-market-sentiment-widgets" in plugin
    assert "Version: 0.6.0" in plugin


def test_plugin_no_longer_outputs_inline_admin_style_block():
    plugin = PLUGIN_FILE.read_text()

    assert "<style>" not in plugin
    assert "admin_enqueue_scripts" in plugin
    assert "assets/admin.css" in plugin
    assert ADMIN_CSS_FILE.is_file()


def test_widgets_script_avoids_literal_style_html_injection():
    widgets = WIDGETS_FILE.read_text()

    assert "<style>" not in widgets
    assert "document.createElement('style')" in widgets


def test_readme_matches_new_brand_and_contributor():
    readme = README_FILE.read_text()

    assert readme.startswith("=== Adanos Market Sentiment Widgets ===")
    assert "Contributors: adanosorg" in readme
    assert "Stable tag: 0.6.0" in readme
