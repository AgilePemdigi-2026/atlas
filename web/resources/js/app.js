/**
 * Tab switching shared by the console, results, and profile pages.
 *
 * A click on any [role="tab"] selects it inside its tablist and shows the
 * panel named by its aria-controls attribute.
 */
document.addEventListener('click', function (event) {
    var tab = event.target.closest('[role="tab"]');

    if (!tab) {
        return;
    }

    var tablist = tab.closest('[role="tablist"]');

    if (!tablist) {
        return;
    }

    tablist.querySelectorAll('[role="tab"]').forEach(function (peer) {
        var selected = peer === tab;
        var panel = document.getElementById(peer.getAttribute('aria-controls'));

        peer.setAttribute('aria-selected', selected ? 'true' : 'false');

        if (panel) {
            panel.hidden = !selected;
        }
    });
});
