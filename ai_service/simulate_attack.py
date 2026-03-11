import requests
import time

API_URL = "http://localhost:8000/api/predict_anomaly"

def test_login(data, description):
    print(f"--- Testing: {description} ---")
    try:
        response = requests.post(API_URL, json=data)
        if response.status_code == 200:
            result = response.json()
            is_anomaly = result.get('is_anomaly')
            score = result.get('anomaly_score')
            print(f"Result: {'ANOMALY' if is_anomaly else 'NORMAL'}")
            print(f"Anomaly Score: {score:.4f}")
        else:
            print(f"Error: HTTP {response.status_code} - {response.text}")
    except requests.exceptions.ConnectionError:
        print("Error: Could not connect to API. Is the server running?")
    print("-" * 40)
    time.sleep(1)

if __name__ == "__main__":
    # Test 1: Normal Login (Business Hours, Desktop)
    normal_data = {
        "user_id": 1,
        "ip_address": "127.0.0.1",
        "geo_location": "Localhost",
        "user_agent": "Mozilla/5.0...",
        "device_type": "Desktop",
        "time_category": "Business Hours",
        "day_of_week": "Tuesday"
    }
    test_login(normal_data, "Normal Login")
    
    # Test 2: Another Normal Login (Mobile, Business Hours)
    normal_mobile = {
        "user_id": 2,
        "ip_address": "192.168.1.5",
        "geo_location": "Localhost",
        "user_agent": "Mozilla/5.0 (iPhone...",
        "device_type": "Mobile",
        "time_category": "Business Hours",
        "day_of_week": "Wednesday"
    }
    test_login(normal_mobile, "Normal Mobile Login")

    # Test 3: Anomalous Login (Rare combination, e.g. Night + Tablet)
    # Since our training data is simple (mostly Desktop Business Hours), 
    # Tablet + Night might trigger an anomaly.
    anomalous_data = {
        "user_id": 3,
        "ip_address": "8.8.8.8",
        "geo_location": "Foreign Location",
        "user_agent": "Bot script...",
        "device_type": "Tablet",
        "time_category": "Night",
        "day_of_week": "Sunday"
    }
    test_login(anomalous_data, "Potentially Anomalous Login (Tablet at Night on Sunday)")
