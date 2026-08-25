// public/js/auction.js

const playerIdOnBlock = 1; 
const teamId = 5; 

function getTournamentQuery() {
    if (window.activeTournamentCode) {
        return `&t=${encodeURIComponent(window.activeTournamentCode)}`;
    }
    const urlParams = new URLSearchParams(window.location.search);
    const t = urlParams.get('t');
    return t ? `&t=${encodeURIComponent(t)}` : '';
}

// 1. Fetch live auction data every 1.5 seconds
setInterval(fetchLiveAuctionData, 1500);

async function fetchLiveAuctionData() {
    try {
        const response = await fetch(`../api/get_live_bid.php?player_id=${playerIdOnBlock}${getTournamentQuery()}`);
        const data = await response.json();

        // Update the UI with the fetched data
        const currentBidEl = document.getElementById('current-bid');
        const leadingTeamEl = document.getElementById('leading-team');

        if (currentBidEl) {
            currentBidEl.innerText = '₹' + data.highest_bid;
        }
        if (leadingTeamEl) {
            leadingTeamEl.innerText = data.leading_team_name || 'No bids yet';
        }
    } catch (error) {
        console.error("Error fetching live data:", error);
    }
}

// 2. Submit a new bid securely
async function placeBid(bidAmount) {
    const formData = new FormData();
    formData.append('player_id', playerIdOnBlock);
    formData.append('team_id', teamId);
    formData.append('bid_amount', bidAmount);
    if (window.activeTournamentCode) {
        formData.append('t', window.activeTournamentCode);
    }

    try {
        const response = await fetch('../api/place_bid.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log("Bid placed successfully!");
            fetchLiveAuctionData();
        } else {
            alert("Bid Failed: " + result.error);
        }
    } catch (error) {
        console.error("Bid submission failed:", error);
    }
}
