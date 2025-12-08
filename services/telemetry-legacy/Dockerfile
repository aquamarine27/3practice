FROM python:3.12-slim


RUN apt-get update && apt-get install -y --no-install-recommends \
    gcc libpq-dev && \
    pip install --no-cache-dir psycopg2-binary pandas xlsxwriter && \
    apt-get remove -y gcc libpq-dev && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY legacy.py .

ENV PYTHONUNBUFFERED=1
CMD ["python", "legacy.py"]