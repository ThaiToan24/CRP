import pandas as pd
import random
from faker import Faker

fake = Faker()

def generate_synthetic_logins(num_records=1000):
    data = []
    
    device_types = ['Desktop', 'Mobile', 'Tablet']
    time_categories = ['Business Hours', 'Night']
    days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
    
    for _ in range(num_records):
        # Normal behavior distribution
        device = random.choices(device_types, weights=[0.6, 0.3, 0.1])[0]
        time_cat = random.choices(time_categories, weights=[0.8, 0.2])[0]
        day = random.choice(days_of_week)
        
        data.append({
            'device_type': device,
            'time_category': time_cat,
            'day_of_week': day
        })
        
    df = pd.DataFrame(data)
    df.to_csv('synthetic_logins.csv', index=False)
    print(f"Generated {num_records} synthetic login records and saved to 'synthetic_logins.csv'")

if __name__ == "__main__":
    generate_synthetic_logins()
