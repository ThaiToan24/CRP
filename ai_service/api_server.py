from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import joblib
import pandas as pd
import uvicorn
import os

app = FastAPI(title="Anomaly Detection API")

# Load model pipeline on startup
model_pipeline = None

class LoginData(BaseModel):
    user_id: int
    ip_address: str
    geo_location: str
    user_agent: str
    device_type: str
    time_category: str
    day_of_week: str

@app.on_event("startup")
def load_model():
    global model_pipeline
    model_path = os.path.join(os.path.dirname(__file__), "isolation_forest_pipeline.pkl")
    if os.path.exists(model_path):
        model_pipeline = joblib.load(model_path)
        print("Model loaded successfully.")
    else:
        print("Warning: Model file not found. Please train the model first.")

@app.post("/api/predict_anomaly")
def predict_anomaly(data: LoginData):
    global model_pipeline
    
    if model_pipeline is None:
        raise HTTPException(status_code=503, detail="Model not loaded. Service unavailable.")
        
    # Prepare data for prediction
    # The Isolation Forest pipeline expects a DataFrame with the same columns it was trained on
    input_df = pd.DataFrame([{
        'device_type': data.device_type,
        'time_category': data.time_category,
        'day_of_week': data.day_of_week
    }])
    
    try:
        # Predict anomaly
        # IsolationForest returns 1 for inliers and -1 for outliers
        prediction = model_pipeline.predict(input_df)[0]
        
        # Calculate anomaly score (lower means more abnormal)
        score = float(model_pipeline.decision_function(input_df)[0])
        
        is_anomaly = True if prediction == -1 else False
        
        return {
            "is_anomaly": is_anomaly,
            "anomaly_score": score,
            "prediction": int(prediction),
            "status": "success"
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error predicting anomaly: {str(e)}")

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000)
