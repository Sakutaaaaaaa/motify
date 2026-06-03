import pymysql
import json
from datetime import datetime

def generate_monthly_report():
    try:
        # 1. Connect to the database
        connection = pymysql.connect(
            host='localhost',
            user='root',
            password='',
            database='motify_db',
            cursorclass=pymysql.cursors.DictCursor
        )
        
        with connection.cursor() as cursor:
            # 2. SQL Query: Get total sales and transaction count for the current month
            current_month = datetime.now().strftime('%Y-%m')
            
            sql = """
                SELECT 
                    COUNT(sales_id) as total_transactions,
                    SUM(total_amount) as total_revenue
                FROM Sales 
                WHERE DATE_FORMAT(transaction_date, '%Y-%m') = %s
            """
            cursor.execute(sql, (current_month,))
            result = cursor.fetchone()

            # Handle null values if there are no sales yet
            report_data = {
                "status": "success",
                "month": current_month,
                "total_transactions": result['total_transactions'] or 0,
                "total_revenue": float(result['total_revenue'] or 0.0)
            }
            
            # 3. Print as JSON (This is what PHP will "catch")
            print(json.dumps(report_data))

    except Exception as e:
        error_response = {"status": "error", "message": str(e)}
        print(json.dumps(error_response))
        
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()

if __name__ == "__main__":
    generate_monthly_report()