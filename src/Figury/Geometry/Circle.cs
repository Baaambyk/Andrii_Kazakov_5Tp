namespace Figury.Geometry;

/// <summary>Obliczenia geometryczne dla koła.</summary>
public static class Circle
{
    /// <summary>Obwód koła (długość okręgu): L = 2 · π · r.</summary>
    public static double Circumference(double radius)
    {
        if (radius <= 0)
        {
            throw new ArgumentOutOfRangeException(nameof(radius), "Promień musi być dodatni.");
        }

        return 2.0 * Math.PI * radius;
    }

    /// <summary>Obwód koła ze średnicy: L = π · d.</summary>
    public static double CircumferenceFromDiameter(double diameter) => Math.PI * diameter;

    /// <summary>Pole koła: P = π · r².</summary>
    public static double Area(double radius)
    {
        if (radius <= 0)
        {
            throw new ArgumentOutOfRangeException(nameof(radius), "Promień musi być dodatni.");
        }

        return Math.PI * radius * radius;
    }
}
