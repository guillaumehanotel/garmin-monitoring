import datetime
import json
import os
import argparse

import requests
from garth.exc import GarthHTTPError
from dotenv import load_dotenv

# Charger les variables d'environnement depuis .env
load_dotenv()

from garminconnect import (
    Garmin,
    GarminConnectAuthenticationError,
    GarminConnectConnectionError,
    GarminConnectTooManyRequestsError,
)

# Load environment variables if defined
# Utiliser les variables d'environnement
email = os.getenv("GARMIN_EMAIL")
password = os.getenv("GARMIN_PASSWORD")

# Chemin par défaut si la variable d'environnement GARMINTOKENS n'est pas définie
default_tokenstore_path = "../.garminconnect"

# Obtenez la valeur de la variable d'environnement GARMINTOKENS
tokenstore = os.getenv("GARMINTOKENS")

# Si GARMINTOKENS n'est pas définie, vérifiez si le dossier existe dans "./.garminconnect"
if not tokenstore:
    if os.path.exists("./.garminconnect"):
        tokenstore = "./.garminconnect"
    else:
        tokenstore = default_tokenstore_path


parser = argparse.ArgumentParser(description="Run Garmin Data Fetch")
parser.add_argument('--activity_type', type=str, help='Type of activity', default="running", choices=["cycling", "running", "swimming", "multi_sport", "fitness_equipment", "hiking", "walking", "other"])
parser.add_argument('--start_date', type=str, help='Start date (YYYY-MM-DD)', default="2024-01-01")
parser.add_argument('--end_date', type=str, help='End date (YYYY-MM-DD), optional')

args = parser.parse_args()

activity_type = args.activity_type
start_date = datetime.datetime.strptime(args.start_date, '%Y-%m-%d').date()
end_date = datetime.datetime.strptime(args.end_date, '%Y-%m-%d').date() if args.end_date else datetime.date.today()


api = None

def display_json(api_call, output):
    """Format API output for better readability."""

    dashed = "-" * 20
    header = f"{dashed} {api_call} {dashed}"
    footer = "-" * len(header)

    print(header)

    if isinstance(output, (int, str, dict, list)):
        print(json.dumps(output, indent=4))
    else:
        print(output)

    print(footer)


def init_api(email, password):
    """Initialize Garmin API with your credentials."""

    try:
        garmin = Garmin()
        garmin.login(tokenstore)
    except (FileNotFoundError, GarthHTTPError, GarminConnectAuthenticationError):
        # Session is expired. You'll need to log in again
        try:
            # Ask for credentials if not set as environment variables
            if not email or not password:
                email = os.getenv("GARMIN_EMAIL")
                password = os.getenv("GARMIN_PASSWORD")

            garmin = Garmin(email, password)
            garmin.login()
            # Save tokens for next login
            garmin.garth.dump(tokenstore)

        except (FileNotFoundError, GarthHTTPError, GarminConnectAuthenticationError, requests.exceptions.HTTPError) as err:
            logger.error(err)
            return None

    return garmin


def display_important_activity_info(activity):
    """Display important information of a running activity."""
    # distance en km
    distance = round(activity["distance"] / 1000, 2)
    # durée en minutes
    duration = round(activity["duration"] / 60, 2)
    # vitesse moyenne en min/km
    average_pace = round(duration / distance, 2)

    important_info = {
        "activity_id": activity["activityId"],
        "name": activity["activityName"],
        "start_time_local": activity["startTimeLocal"],
        "start_time_gmt": activity["startTimeGMT"],
        "distance_km": distance,
        "duration_minutes": duration,
        "average_pace_min_per_km": average_pace,
        "average_heart_rate_bpm": activity.get("averageHR", "N/A"),
        "max_heart_rate_bpm": activity.get("maxHR", "N/A"),
        "average_running_cadence_steps_per_min": round(activity.get("averageRunningCadenceInStepsPerMinute", "N/A")),
        "max_running_cadence_steps_per_min": round(activity.get("maxRunningCadenceInStepsPerMinute", "N/A")),
        "location": activity.get("locationName", "N/A")
    }

    return important_info


if __name__ == '__main__':

    api = init_api(email, password)
    if api is not None:
        activities_data = []

        activities = api.get_activities_by_date(
            start_date.isoformat(), end_date.isoformat(), activity_type
        )

        for activity in activities:
            activities_data.append(display_important_activity_info(activity))

            # Retourner les données en JSON
        print(json.dumps(activities_data))

