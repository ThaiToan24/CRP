import pandas as pd
from sklearn.ensemble import IsolationForest
from sklearn.preprocessing import OneHotEncoder
from sklearn.pipeline import Pipeline
from sklearn.compose import ColumnTransformer
import joblib

def train_isolation_forest():
    print("Loading data...")
    try:
        df = pd.read_csv('synthetic_logins.csv')
    except FileNotFoundError:
        print("Error: synthetic_logins.csv not found. Run generate_data.py first.")
        return

    # Define categorical columns
    categorical_cols = ['device_type', 'time_category', 'day_of_week']
    
    # Create preprocessing pipeline
    preprocessor = ColumnTransformer(
        transformers=[
            ('cat', OneHotEncoder(handle_unknown='ignore'), categorical_cols)
        ]
    )
    
    # Create the Isolation Forest model
    # contamination defines the proportion of outliers in the data set
    model = IsolationForest(contamination=0.05, random_state=42)
    
    # Create the full pipeline
    pipeline = Pipeline(steps=[
        ('preprocessor', preprocessor),
        ('model', model)
    ])
    
    print("Training model...")
    pipeline.fit(df)
    
    print("Saving model to isolation_forest_pipeline.pkl...")
    joblib.dump(pipeline, 'isolation_forest_pipeline.pkl')
    print("Training complete.")

if __name__ == "__main__":
    train_isolation_forest()
